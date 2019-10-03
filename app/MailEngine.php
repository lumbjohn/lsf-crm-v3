<?php

namespace App;

// TODO: import PHPMailer

class MailEngine
{
    private static function DefaultTemplate()
    {
        return '##MESAGE_BODY##';

        /*
        return '<table width="688" border="0" cellspacing="0" cellpadding="0" align="center" style="font-family:Arial, Helvetica, sans-serif;font-size:14px;" >
            <tr>
                <td width="688" valign="top" style="padding:18px;">

                    <table width="649" border="0" cellspacing="0" cellpadding="0">
                        <tr>
                            <td  valign="top"><a href="" ><img src="https://lsf-energie.fr/" border="0" style="width:100px" ></a></td>
                        </tr>
                        <tr>
                            <td valign="top" style="padding:25px;">
                                ##MESAGE_BODY##
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            </table>';*/
    }

    private static function createMail()
    {
        // FIXME: change credentials

        $mail = new PHPMailer;
        $mail->CharSet = 'UTF-8';

        //$mail->SMTPDebug = 3;                               // Enable verbose debug output

        /*$mail->isSMTP();                                      // Set mailer to use SMTP
        $mail->Host = 'smtp1.example.com;smtp2.example.com';  // Specify main and backup SMTP servers
        $mail->SMTPAuth = true;                               // Enable SMTP authentication
        $mail->Username = 'user@example.com';                 // SMTP username
        $mail->Password = 'secret';                           // SMTP password
        $mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
        $mail->Port = 587;                                    // TCP port to connect to
        */

        $mail->isSMTP();                                      // Set mailer to use SMTP
        $mail->Host = 'smtp.gmail.com';  // Specify main and backup SMTP servers
        $mail->SMTPAuth = true;                               // Enable SMTP authentication
        $mail->Username = 'system@lsf-iso.fr';                 // SMTP username
        $mail->Password = 'Sys55@26!';                           // SMTP password
        $mail->SMTPSecure = 'ssl';                            // Enable TLS encryption, `ssl` also accepted
        $mail->Port = 465;

        $mail->From = 'system@lsf-iso.fr';
        $mail->FromName = 'LSF ENERGIE';


        return $mail;
    }

    public static function sendMail($type, $vals)
    {
        switch ($type) {
            case 'mail-entrepot' :

                $mail = MailEngine::createMail();

                $mail->addAddress($vals->email, $vals->entrepot_name);     // Add a recipient
                $mail->isHTML(true);                                  // Set email format to HTML

                $mail->Subject = $vals->subject;

                $mail->Body = str_replace('##MESAGE_BODY##', $vals->msg, MailEngine::DefaultTemplate());

                if (!$mail->send()) {
                    echo 'Message could not be sent.';
                    echo 'Mailer Error: ' . $mail->ErrorInfo;
                } else {
                    //echo 'Message has been sent';
                }

                break;
        }
    }

}
