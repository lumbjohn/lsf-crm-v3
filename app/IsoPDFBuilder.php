<?php

namespace App;

// TODO: import HTML2PDF

class IsoPDFBuilder
{
    public static function checkDir($rep)
    {
        $dirup = __DIR__ . '/../storage/uploads/';
        $dir = $dirup . $rep;
        if (!is_dir($dir)) {
            mkdir($dir);
            copy($dirup . 'index.php', $dir . '/index.php');
        }

        return $dir;
    }

    public static function BuildContactDoc($rep, $docname, $content, $local = true)
    {
        $dir = IsoPDFBuilder::checkDir($rep);
        $htmldoc = '<style type="text/css" media="screen,print">
						<!--
						* {
							font-family: Arial;
						}
						.latolight {font-family:latolight;}
						-->
					</style>
					<page backtop="17mm" backbottom="14mm" backleft="5mm" backright="5mm">
						' . $content . '

					</page>';

        // require_once('lib/html2pdf/vendor/autoload.php');
        try {
            $html2pdf = new HTML2PDF('P', 'A4', 'fr');
            $html2pdf->WriteHTML($htmldoc);
            // $html2pdf->addFont('latoregular', '', 'latoregular');
            $filename = $dir . '/' . $docname;
            $html2pdf->Output($filename, 'F');
            return $local ? $filename : 'uploads/' . $rep . '/' . $docname . '?time=' . time();

        } catch (HTML2PDF_exception $e) {
            return $e;
            exit;
        }
    }
}
