<?php

namespace App\Services;

use App\Models\DocumentSignature;
use Exception;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Tcpdf\Fpdi;

class PdfStamperService
{
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
        if (! $document->signature_data || ! Storage::disk('private')->exists($document->original_pdf_path)) {
            throw new Exception('Données de signature ou PDF original manquants.');
        }

        $originalPath = Storage::disk('private')->path($document->original_pdf_path);

        // 2. Initialisation de FPDI/TCPDF
        $pdf = new Fpdi;
        $pageCount = $pdf->setSourceFile($originalPath);

        // 3. Importation et copie de toutes les pages
        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateId = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($templateId);

            // Conserver l'orientation originale
            $orientation = $size['width'] > $size['height'] ? 'L' : 'P';
            $pdf->AddPage($orientation, [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId);

            // 4. Si c'est la dernière page, on incruste la signature
            if ($pageNo === $pageCount) {
                $this->applySignatureToPage($pdf, $document);
            }
        }

        // 5. Sauvegarde du nouveau fichier
        $newFileName = 'quotes/signed/signed_'.$document->uuid.'.pdf';
        $newFilePath = Storage::disk('private')->path($newFileName);

        // Assurez-vous que le dossier existe
        Storage::disk('private')->makeDirectory('quotes/signed');

        $pdf->Output($newFilePath, 'F');

        return $newFileName;
    }

    /**
     * Logique de positionnement de l'image et du texte légal sur la page.
     */
    private function applySignatureToPage(Fpdi $pdf, DocumentSignature $document): void
    {
        // Nettoyage du Base64 pour TCPDF
        $imgData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $document->signature_data));

        // TCPDF a besoin d'un fichier physique pour l'image (astuce: fichier temporaire)
        $tmpImg = tmpfile();
        $tmpImgMeta = stream_get_meta_data($tmpImg);
        $tmpImgPath = $tmpImgMeta['uri'];
        file_put_contents($tmpImgPath, $imgData);

        // Positionnement (Exemple: en bas à droite de la page)
        $x = 130;
        $y = 230;
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
