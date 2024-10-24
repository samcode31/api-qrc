<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Codedge\Fpdf\Fpdf\Fpdf;

class Pdf extends Fpdf
{
    public $widths;
    public $aligns;
    public $borders;
    public $fills;
    
    public function SetWidths($w)
    {
        //Set the array of column widths
        $this->widths=$w;
    }

    public function SetAligns($a)
    {
        //Set the array of column alignments
        $this->aligns=$a;
    }

    public function SetBorders($b)
    {
        //Set the array of borders
        $this->borders=$b;
    }

    public function SetFills($f)
    {
        //Set the cell fill
        $this->fills=$f;
    }


    public function Row($data, $fill)
    {
        //Calculate the height of the row
        $nb=0; 
        $nbMax=0; 
        $passmark = 50;
        
        for($i=0;$i<count($data);$i++)
        $nbMax=max($nbMax,$this->NbLines($this->widths[$i],$data[$i]));
        $h=5*$nbMax;
        
        //Issue a page break first if needed
        $this->CheckPageBreak($h);
        //Draw the cells of the row
        for($i=0;$i<count($data);$i++)
        {
            $nb=$this->NbLines($this->widths[$i],$data[$i]);
            if($nb == 0) $nb = 1;
            $w=$this->widths[$i];
            $a=isset($this->aligns[$i]) ? $this->aligns[$i] : 'L';
            //Save the current position
            $x=$this->GetX();
            $y=$this->GetY();
            //set mark to red
            if($i == 1 && is_numeric($data[$i]) && $data[$i] < $passmark) $this->SetTextColor(255, 0, 0);
            if($i == 2 && is_numeric($data[$i]) && $data[$i] < $passmark) $this->SetTextColor(255, 0, 0);
            //Print the text
                          
            $this->MultiCell($w,bcdiv($h,$nb,1),$data[$i],$this->borders[$i],$a,$fill); 
            $this->SetFont('Times','','10');           
            
            $this->SetTextColor(0, 0, 0);           
            
            //Put the position to the right of the cell
            $this->SetXY($x+$w,$y);
        }
        //Go to the next line
        $this->Ln($h);
    }

    private function CheckPageBreak($h)
    {
        //If the height h would cause an overflow, add a new page immediately
        if($this->GetY()+$h>$this->PageBreakTrigger)
            $this->AddPage($this->CurOrientation);
    }

    public function NbLines($w,$txt)
    {
        //Computes the number of lines a MultiCell of width w will take
        $cw=&$this->CurrentFont['cw'];
        if($w==0)
            $w=$this->w-$this->rMargin-$this->x;
        $wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
        $s=str_replace("\r",'',$txt);
        $nb=strlen($s);
        if($nb>0 and $s[$nb-1]=="\n")
            $nb--;
        $sep=-1;
        $i=0;
        $j=0;
        $l=0;
        $nl=1;
        while($i<$nb)
        {
            $c=$s[$i];
            if($c=="\n")
            {
                $i++;
                $sep=-1;
                $j=$i;
                $l=0;
                $nl++;
                continue;
            }
            if($c==' ')
                $sep=$i;
            $l+=$cw[$c];
            if($l>$wmax)
            {
                if($sep==-1)
                {
                    if($i==$j)
                        $i++;
                }
                else
                    $i=$sep+1;
                $sep=-1;
                $j=$i;
                $l=0;
                $nl++;
            }
            else
                $i++;
        }
        return $nl;
    }

    public function SetDash($black=null, $white=null)
    {
        if($black!==null)
            $s=sprintf('[%.3F %.3F] 0 d',$black*$this->k,$white*$this->k);
        else
            $s='[] 0 d';
        $this->_out($s);
    }

    public function RotateText($txt, $angle, $cellWidth, $xOffset=15)
    {

        $x = $this->GetX();

        $y = $this->GetY();

        $yOffset = ($cellWidth / 2) - 1;

        $this->Rotate($angle, $x, $y);

        $this->Text($x - $xOffset, $y - $yOffset, $txt);

        $this->Rotate(0);

    }

    public function RotateTextF6($txt, $angle)
    {

        $x = $this->GetX();

        $y = $this->GetY();       

        $this->Rotate($angle, $x, $y);

        $this->Text($x, $y, $txt);

        $this->Rotate(0);

    }

    var $angle=0;

    public function Rotate($angle,$x=-1,$y=-1)
    {
        if($x==-1)
            $x=$this->x;
        if($y==-1)
            $y=$this->y;
        if($this->angle!=0)
            $this->_out('Q');
        $this->angle=$angle;
        if($angle!=0)
        {
            $angle*=M_PI/180;
            $c=cos($angle);
            $s=sin($angle);
            $cx=$x*$this->k;
            $cy=($this->h-$y)*$this->k;
            $this->_out(sprintf('q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm',$c,$s,-$s,$c,$cx,$cy,-$cx,-$cy));
        }
    }

    public function RotateImage($file, $x, $y, $w, $h, $angle)
    {
        //Image rotated around its upper-left corner
        $this->Rotate($angle,$x,$y);
        $this->Image($file,$x,$y,$w,$h);
        $this->Rotate(0);
    }

}
