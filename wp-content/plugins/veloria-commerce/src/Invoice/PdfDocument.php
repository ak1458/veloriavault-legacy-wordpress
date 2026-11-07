<?php
/**
 * Tiny single-page PDF writer: standard Helvetica / Helvetica-Bold text and
 * lines, A4. Enough to reproduce the pdf-lib invoice exactly without adding a
 * PDF library dependency. Coordinates are PDF points from the bottom-left,
 * the same system pdf-lib uses.
 *
 * @package Veloria\Commerce
 */

namespace Veloria\Commerce\Invoice;

defined( 'ABSPATH' ) || exit;

final class PdfDocument {

	/** Adobe Helvetica AFM advance widths (1/1000 em), chars 32..126. */
	private const WIDTHS = array(
		'F1' => array( 278, 278, 355, 556, 556, 889, 667, 191, 333, 333, 389, 584, 278, 333, 278, 278, 556, 556, 556, 556, 556, 556, 556, 556, 556, 556, 278, 278, 584, 584, 584, 556, 1015, 667, 667, 722, 722, 667, 611, 778, 722, 278, 500, 667, 556, 833, 722, 778, 667, 778, 722, 667, 611, 722, 667, 944, 667, 667, 611, 278, 278, 278, 469, 556, 333, 556, 556, 500, 556, 556, 278, 556, 556, 222, 222, 500, 222, 833, 556, 556, 556, 556, 333, 500, 278, 556, 500, 722, 500, 500, 500, 334, 260, 334, 584 ),
		'F2' => array( 278, 333, 474, 556, 556, 889, 722, 238, 333, 333, 389, 584, 278, 333, 278, 278, 556, 556, 556, 556, 556, 556, 556, 556, 556, 556, 333, 333, 584, 584, 584, 611, 975, 722, 722, 722, 722, 667, 611, 778, 722, 278, 556, 722, 611, 833, 722, 778, 667, 778, 722, 667, 611, 722, 667, 944, 667, 667, 611, 333, 278, 333, 584, 556, 333, 556, 611, 556, 611, 556, 333, 611, 611, 278, 278, 556, 278, 889, 611, 611, 611, 611, 389, 556, 333, 611, 556, 778, 556, 556, 500, 389, 280, 389, 584 ),
	);

	private float $width;
	private float $height;
	private string $content = '';

	public function __construct( float $width = 595, float $height = 842 ) {
		$this->width  = $width;
		$this->height = $height;
	}

	/** Width of text in points (non-ASCII glyphs measured as "?"). */
	public function text_width( string $text, float $size, bool $bold = false ): float {
		$table = self::WIDTHS[ $bold ? 'F2' : 'F1' ];
		$units = 0;
		foreach ( str_split( self::to_ascii( $text ) ) as $char ) {
			$code   = ord( $char );
			$units += ( $code >= 32 && $code <= 126 ) ? $table[ $code - 32 ] : 556;
		}
		return $units * $size / 1000;
	}

	/** @param array{0:float,1:float,2:float} $rgb 0..1 */
	public function text( string $text, float $x, float $y, float $size = 10, bool $bold = false, array $rgb = array( 0.1, 0.1, 0.1 ) ): void {
		$this->content .= sprintf(
			"BT %.3F %.3F %.3F rg /%s %.2F Tf %.2F %.2F Td (%s) Tj ET\n",
			$rgb[0],
			$rgb[1],
			$rgb[2],
			$bold ? 'F2' : 'F1',
			$size,
			$x,
			$y,
			self::escape( self::to_ascii( $text ) )
		);
	}

	/** @param array{0:float,1:float,2:float} $rgb */
	public function line( float $x1, float $y1, float $x2, float $y2, float $thickness = 0.5, array $rgb = array( 0.4, 0.4, 0.4 ) ): void {
		$this->content .= sprintf( "%.3F %.3F %.3F RG %.2F w %.2F %.2F m %.2F %.2F l S\n", $rgb[0], $rgb[1], $rgb[2], $thickness, $x1, $y1, $x2, $y2 );
	}

	public function output(): string {
		$objects   = array();
		$objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
		$objects[] = '<< /Type /Pages /Kids [3 0 R] /Count 1 >>';
		$objects[] = sprintf( '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.0F %.0F] /Resources << /Font << /F1 4 0 R /F2 5 0 R >> >> /Contents 6 0 R >>', $this->width, $this->height );
		$objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
		$objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
		$objects[] = '<< /Length ' . strlen( $this->content ) . " >>\nstream\n" . $this->content . "endstream";

		$pdf     = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
		$offsets = array();
		foreach ( $objects as $i => $object ) {
			$offsets[] = strlen( $pdf );
			$pdf      .= ( $i + 1 ) . " 0 obj\n" . $object . "\nendobj\n";
		}
		$xref = strlen( $pdf );
		$pdf .= 'xref' . "\n0 " . ( count( $objects ) + 1 ) . "\n0000000000 65535 f \n";
		foreach ( $offsets as $offset ) {
			$pdf .= sprintf( "%010d 00000 n \n", $offset );
		}
		$pdf .= "trailer\n<< /Size " . ( count( $objects ) + 1 ) . " /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF";
		return $pdf;
	}

	private static function escape( string $text ): string {
		return str_replace( array( '\\', '(', ')', "\r", "\n" ), array( '\\\\', '\\(', '\\)', ' ', ' ' ), $text );
	}

	/** Standard fonts are WinAnsi; transliterate (₹ is written as "Rs." by callers). */
	private static function to_ascii( string $text ): string {
		$converted = function_exists( 'remove_accents' ) ? remove_accents( $text ) : $text;
		$converted = str_replace( array( '–', '—', '’', '‘', '“', '”', '…' ), array( '-', '-', "'", "'", '"', '"', '...' ), $converted );
		return (string) preg_replace( '/[^\x20-\x7E]/', '?', $converted );
	}
}
