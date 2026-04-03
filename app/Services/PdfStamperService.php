<?php

namespace App\Services;

use App\Models\DocumentSignature;
use Exception;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Tcpdf\Fpdi;
use Smalot\PdfParser\Parser;

class PdfStamperService
{
    protected string $searchKeyword = 'signature précédée de la mention :';
    /**
     * Appose la signature sur le PDF et retourne le chemin du nouveau fichier.
     *
     * @return string Le chemin vers le PDF signé
     *
     * @throws Exception
     */
    public function stampSignature(DocumentSignature $document): string
    {
        // 1. Vérification des prérequis
        if (! $document->signature_data || ! Storage::disk('local')->exists($document->original_pdf_path)) {
            throw new Exception('Données de signature ou PDF original manquants.');
        }

        $originalPath = Storage::disk('local')->path($document->original_pdf_path);
        $coords = $this->findCoordinates($originalPath);

        $gsCmd = config('services.ghostscript.cmd');
        $tempUncompressedPath = storage_path('app/private/temp_uncompressed_'.$document->uuid.'.pdf');

        $process = Process::run([
            $gsCmd,
            '-sDEVICE=pdfwrite',
            '-dCompatibilityLevel=1.4', // Force la version lisible par FPDI
            '-dNOPAUSE',
            '-dQUIET',
            '-dBATCH',
            '-sOutputFile='.$tempUncompressedPath,
            $originalPath,
        ]);

        if (! $process->successful()) {
            throw new Exception('Échec de la décompression du PDF par Ghostscript : '.$process->errorOutput());
        }

        // 2. Initialisation de FPDI/TCPDF en utilisant le fichier décompressé
        $pdf = new Fpdi;

        // On source le fichier temporaire au lieu de l'original
        $pageCount = $pdf->setSourceFile($tempUncompressedPath);

        // 3. Importation et copie de toutes les pages
        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateId = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($templateId);

            $orientation = $size['width'] > $size['height'] ? 'L' : 'P';
            $pdf->AddPage($orientation, [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId);

            if ($pageNo === $coords['page']) {
                $this->applySignatureToPage(
                    $pdf,
                    $document,
                    $coords['x'],
                    $coords['y']
                );
            }
        }

        $newFileName = 'quotes/signed/signed_'.$document->uuid.'.pdf';
        $newFilePath = Storage::disk('local')->path($newFileName);

        Storage::disk('local')->makeDirectory('quotes/signed');
        $pdf->Output($newFilePath, 'F');

        // --- NOUVEAU CODE : NETTOYAGE ---
        if (file_exists($tempUncompressedPath)) {
            unlink($tempUncompressedPath);
        }
        // ---------------------------------

        return $newFileName;
    }

    /**
     * Analyse le PDF pour trouver le mot-clé et retourner les coordonnées X, Y et la Page.
     *
     * @throws Exception
     */
    protected function findCoordinates(string $pdfPath): array
    {
        $parser = new Parser;
        $pdf = $parser->parseFile($pdfPath);
        $pages = $pdf->getPages();

        // Paramètres par défaut (fallback en bas à droite de la dernière page)
        $result = [
            'x' => 120,
            'y' => 250,
            'page' => count($pages),
        ];

        foreach ($pages as $pageNumber => $page) {
            // Extraction des données de texte avec positions
            $data = $page->getDataTm();

            foreach ($data as $item) {
                $text = $item[1];
                $position = $item[0]; // [1, 0, 0, 1, X, Y]

                if (str_contains(strtolower($text), strtolower($this->searchKeyword))) {
                    // Les coordonnées PDF (0,0) sont en bas à gauche.
                    // FPDI utilise souvent le haut à gauche, un ajustement peut être nécessaire.
                    return [
                        'x' => $position[4],
                        'y' => $this->calculateY($page, $position[5]),
                        'page' => $pageNumber + 1,
                    ];
                }
            }
        }

        return $result;
    }

    /**
     * Convertit la coordonnée Y (bas vers haut) en coordonnée utilisable (haut vers bas).
     */
    protected function calculateY($page, $pdfY): float
    {
        $details = $page->getDetails();
        $height = $details['MediaBox'][3] ?? 842; // Taille A4 par défaut si non trouvé

        // On remonte un peu au-dessus du texte (ex: -30) pour ne pas écraser le mot-clé
        return ($height - $pdfY) - 30;
    }

    /**
     * Logique de positionnement de l'image et du texte légal sur la page.
     */
    private function applySignatureToPage(Fpdi $pdf, DocumentSignature $document, $x, $y): void
    {
        // Nettoyage du Base64 pour TCPDF
        $imgData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $document->signature_data));

        // TCPDF a besoin d'un fichier physique pour l'image (astuce: fichier temporaire)
        $tmpImg = tmpfile();
        $tmpImgMeta = stream_get_meta_data($tmpImg);
        $tmpImgPath = $tmpImgMeta['uri'];
        file_put_contents($tmpImgPath, $imgData);

        // Positionnement (Exemple: en bas à droite de la page)
        $width = 60; // Largeur de la signature

        // Ajout de l'image
        $pdf->Image($tmpImgPath, $x, $y, $width);

        // Ajout du texte de traçabilité légale en dessous
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(100, 100, 100);
        $legalText = sprintf(
            "Signé numériquement par : %s\nLe : %s\nIP : %s",
            $document->signer_name,
            $document->signed_at->format('d/m/Y à H:i:s'),
            $document->signer_ip
        );
        $pdf->SetXY($x, $y + 25); // Juste sous l'image
        $pdf->MultiCell($width + 10, 5, $legalText, 0, 'L');

        fclose($tmpImg); // Supprime le fichier temporaire
    }
}
