<?php
/**
 * Email Header for Abandoned Cart Recovery
 *
 * @package WooLentor\Templates\Emails
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo esc_attr( get_bloginfo( 'charset' ) ); ?>"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?php echo esc_html( get_bloginfo( 'name', 'display' ) ); ?></title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
    <style type="text/css">
        /* Email client resets */
        body, table, td, p, a, li, blockquote {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }
        
        /* Remove spacing around the email design in some email clients */
        body, table {
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }
        
        /* Use better rendering method when images are resized in IE */
        img {
            -ms-interpolation-mode: bicubic;
            border: 0;
            outline: none;
            text-decoration: none;
        }
        
        /* Prevent iOS and Android auto-linking */
        .appleLinks a {
            color: inherit !important;
            text-decoration: none !important;
        }
        
        /* Prevent Gmail and Outlook from displaying download links for large images */
        .ExternalClass {
            width: 100%;
        }
        .ExternalClass,
        .ExternalClass p,
        .ExternalClass span,
        .ExternalClass font,
        .ExternalClass td,
        .ExternalClass div {
            line-height: 100%;
        }
        
        /* Force Outlook to provide a "view in browser" menu link */
        #outlook a {
            padding: 0;
        }
        
        /* Force Hotmail to display emails at full width */
        .ReadMsgBody {
            width: 100%;
        }
        
        /* Force Hotmail to display normal line spacing */
        .ExternalClass {
            width: 100%;
        }
        
        /* General styles */
        body {
            margin: 0;
            padding: 0;
            width: 100% !important;
            min-width: 100%;
            background-color: #f4f4f4;
        }
        
        /* Styles for better compatibility */
        table {
            border-collapse: collapse;
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }
        
        td {
            padding: 0;
        }
        
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
        }
        
        .email-content {
            padding: 40px 30px;
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 16px;
            line-height: 1.6;
            color: #333333;
        }
        
        /* Responsive styles */
        @media only screen and (max-width: 600px) {
            .email-container {
                width: 100% !important;
                max-width: 100% !important;
            }
            
            .email-content {
                padding: 20px 15px !important;
            }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; width: 100% !important; min-width: 100%; background-color: #f4f4f4;" bgcolor="#f4f4f4">
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin: 0; padding: 0;">
        <tr>
            <td style="padding: 20px 0;" align="center" valign="top">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" class="email-container" style="margin: 0 auto; max-width: 600px; background-color: #ffffff;" bgcolor="#ffffff">
                    <tr>
                        <td class="email-content" style="padding: 10px 20px; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 16px; line-height: 1.6; color: #333333;">