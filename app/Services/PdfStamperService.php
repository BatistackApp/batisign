<?php

namespace App\Services;

use App\Enums\SignatureStatus;
use App\Models\DocumentSignature;
use Exception;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Tcpdf\Fpdi;
use Smalot\PdfParser\Parser;

class PdfStamperService
{
    /**
     * Mot-clé recherché dans le PDF pour le positionnement.
     */
    protected string $searchKeyword = 'signature précédée de la mention :';

    /**
     * Appose la signature, ajoute un dossier de preuve et sécurise l'intégrité.
     * @throws Exception
     */
    public function stampSignature(DocumentSignature $document, array $metadata = []): string
    {
        // 1. Vérification des prérequis
        if (! $document->signature_data || ! Storage::disk('local')->exists($document->original_pdf_path)) {
            throw new Exception('Données de signature ou PDF original manquants.');
        }

        $originalPath = Storage::disk('local')->path($document->original_pdf_path);
        $tempUncompressedPath = storage_path('app/private/temp_uncompressed_'.$document->uuid.'.pdf');

        // On s'assure que le dossier de destination existe
        Storage::disk('local')->makeDirectory('signed');

        try {
            // 2. Conversion/Décompression via Ghostscript
            $this->decompressPdf($originalPath, $tempUncompressedPath);

            // 3. Analyse pour trouver les coordonnées dynamiques
            $coords = $this->findCoordinates($tempUncompressedPath);

            // 4. Initialisation du scellement avec FPDI (Version TCPDF)
            // On démarre un tampon pour capturer toute sortie accidentelle
            ob_start();

            $pdf = new Fpdi();

            // On désactive les en-têtes et pieds de page par défaut de TCPDF
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);

            $pageCount = $pdf->setSourceFile($tempUncompressedPath);

            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $templateId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($templateId);

                // Détection de l'orientation
                $orientation = $size['width'] > $size['height'] ? 'L' : 'P';

                $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId);

                if ($pageNo === $coords['page']) {
                    $this->applySignatureToPage($pdf, $document, $coords['x'], $coords['y'], $metadata);
                }
            }

            // 5. Ajout de la page "Dossier de Preuve"
            $this->addAuditTrailPage($pdf, $document, $metadata);

            // 6. Sortie sécurisée pour TCPDF
            // Pour TCPDF, la signature est Output($name, $dest).
            // En mettant une chaîne vide en nom et 'S' en destination, on force le retour en STRING.
            $content = $pdf->Output('', 'S');

            // Nettoyage du tampon de sortie
            if (ob_get_length()) {
                ob_end_clean();
            }

            if (empty($content)) {
                throw new Exception('Le contenu du PDF généré est vide (Erreur TCPDF).');
            }

            $signedPath = 'signed/'.$document->uuid.'.pdf';
            Storage::disk('local')->put($signedPath, $content);

            // 7. Mise à jour de la base de données
            $document->update([
                'status' => SignatureStatus::SIGNED,
                'signed_at' => now(),
                'pdf_hash' => hash('sha256', $content),
            ]);

            return $signedPath;

        } catch (Exception $e) {
            if (ob_get_length()) {
                ob_end_clean();
            }
            throw $e;
        } finally {
            // Nettoyage rigoureux du fichier temporaire
            if (file_exists($tempUncompressedPath)) {
                @unlink($tempUncompressedPath);
            }
        }
    }

    /**
     * Utilise Ghostscript pour rendre le PDF compatible (Version 1.4).
     */
    private function decompressPdf(string $source, string $destination): void
    {
        $gsCmd = config('services.ghostscript.cmd', 'gs');

        $process = Process::run([
            $gsCmd,
            '-sDEVICE=pdfwrite',
            '-dCompatibilityLevel=1.4',
            '-dNOPAUSE',
            '-dQUIET',
            '-dBATCH',
            '-sOutputFile='.$destination,
            $source,
        ]);

        if ($process->failed()) {
            throw new Exception('Erreur Ghostscript : '.$process->errorOutput());
        }
    }

    /**
     * Analyse le PDF pour localiser le mot-clé.
     */
    private function findCoordinates(string $pdfPath): array
    {
        $parser = new Parser;
        $pdf = $parser->parseFile($pdfPath);
        $pages = $pdf->getPages();

        $result = ['x' => 120, 'y' => 200, 'page' => count($pages)];

        foreach ($pages as $index => $page) {
            $textData = $page->getDataTm();
            foreach ($textData as $item) {
                if (str_contains(strtolower($item[1]), strtolower($this->searchKeyword))) {
                    return [
                        'x' => $item[0][4],
                        'y' => $this->calculateY($page, $item[0][5]),
                        'page' => $index + 1,
                    ];
                }
            }
        }

        return $result;
    }

    /**
     * Appose l'image de signature.
     */
    private function applySignatureToPage(Fpdi $pdf, DocumentSignature $document, $x, $y, array $metadata): void
    {
        $imgData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $document->signature_data));

        $tmpFile = tempnam(sys_get_temp_dir(), 'sig');
        file_put_contents($tmpFile, $imgData);

        try {
            // Dans TCPDF, Image($file, $x, $y, $w, $h, $type, $link, $align, $resize, $dpi, $palign, $ismask, $imgmask, $border, $fitbox, $hidden, $fitonpage)
            $pdf->Image($tmpFile, $x, $y, 50);

            $pdf->SetFont('helvetica', '', 7);
            $pdf->SetTextColor(120, 120, 120);
            $pdf->SetXY($x, $y + 15);

            $text = sprintf(
                "Signe par : %s\nLe : %s\nIP : %s",
                utf8_decode($document->signer_name ?? $document->client_name),
                now()->format('d/m/Y H:i'),
                $metadata['ip'] ?? 'N/A'
            );
            $pdf->MultiCell(50, 3, $text, 0, 'L');
        } finally {
            if (file_exists($tmpFile)) {
                @unlink($tmpFile);
            }
        }
    }

    /**
     * Ajoute le certificat d'audit final.
     */
    private function addAuditTrailPage(Fpdi $pdf, DocumentSignature $document, array $metadata): void
    {
        $pdf->AddPage('P', 'A4');
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(0, 15, utf8_decode('Certificat de Signature Électronique'), 0, 1, 'C');
        $pdf->Ln(5);

        $pdf->SetFont('helvetica', '', 10);
        $pdf->MultiCell(0, 5, utf8_decode("Ce document est certifié par Batisign. Il contient les informations de traçabilité garantissant l'intégrité de la signature et du document original."));
        $pdf->Ln(10);

        $pdf->SetFillColor(245, 245, 245);
        $data = [
            'Reference Document' => $document->uuid,
            'Client / Signataire' => $document->signer_name ?? $document->client_name,
            'Horodatage Signature' => now()->format('d/m/Y H:i:s').' UTC',
            'Adresse IP' => $metadata['ip'] ?? 'Inconnue',
            'Navigateur' => substr($metadata['user_agent'] ?? 'Inconnu', 0, 80),
            'Empreinte (SHA-256)' => hash('sha256', $document->uuid.$document->signed_at),
        ];

        foreach ($data as $label => $value) {
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->Cell(55, 8, utf8_decode($label), 1, 0, 'L', true);
            $pdf->SetFont('helvetica', '', 9);
            $pdf->Cell(0, 8, utf8_decode($value), 1, 1, 'L');
        }

        $pdf->Ln(15);
        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->SetTextColor(150, 150, 150);
        $pdf->MultiCell(0, 4, utf8_decode("L'empreinte numérique SHA-256 stockée dans nos registres permet de vérifier qu'aucune modification n'a été apportée au fichier après sa signature."));
    }

    private function calculateY($page, $pdfY): float
    {
        $details = $page->getDetails();
        $height = $details['MediaBox'][3] ?? 842;

        return ($height - $pdfY) - 25;
    }
}
