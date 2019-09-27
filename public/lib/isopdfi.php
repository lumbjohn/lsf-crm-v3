<?php

require_once('fpdf/fpdf.php');
require_once('fpdi/fpdi.php');

class ISOPDFI extends FPDI
{
	
	public $isoref;
	public $typedoc;

	function Header()
	{


	}

	function Footer()
	{
		// Positionnement � 1,5 cm du bas
		/*$this->setXY(170, -15);
		$this->setFillColor(255,255,255);
		// Num�ro de page
		$nop = $this->PageNo();
		if ($nop > 8)
			$nop -= 4;
		if ($nop > 4)
			$nop -= 4;
		$this->Cell(50,10,$nop.'/4',0,0,'C', true);
		
		if ($nop == 1) {
			if ($this->typedoc == '101')
				$this->setXY(2, -18);
			else
			if ($this->typedoc == '102')
				$this->setXY(8, -21);
			else
			if ($this->typedoc == '103')
				$this->setXY(13, -21);
			
			$this->Cell(0,10,$this->isoref,0,0,'C');
		}
		else
		if ($nop == 2) {
			if ($this->typedoc == '101')
				$this->setXY(8, -20);
			else
			if ($this->typedoc == '102')
				$this->setXY(8, -21);
			else
			if ($this->typedoc == '103')
				$this->setXY(13, -21);
			$this->Cell(0,10,$this->isoref,0,0,'C');
		}
		else
		if ($nop == 3) {
			if ($this->typedoc == '101')
				$this->setXY(8, -22);
			else
			if ($this->typedoc == '102')
				$this->setXY(8, -21);
			else
			if ($this->typedoc == '103')
				$this->setXY(13, -21);
			$this->Cell(0,10,$this->isoref,0,0,'C');
		}
		else
		if ($nop == 4) {
			if ($this->typedoc == '101')
				$this->setXY(14, -17);
			else
			if ($this->typedoc == '102')
				$this->setXY(14, -15);
			else
			if ($this->typedoc == '103')
				$this->setXY(17, -18);
			$this->Cell(0,10,$this->isoref,0,0,'C');
		}*/
	}

}