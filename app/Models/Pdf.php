<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Codedge\Fpdf\Fpdf\Fpdf;

class Pdf extends Fpdf
{
    public $widths;
    public $aligns;

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

    public function Row($data, $fill=false, $border=1)
    {
        //Calculate the height of the row
        $nb=0; $nbMax=0; $noComment = false; $teacherCol = 9; $teacherInitialOffset = 62; $passmark = 50;

        for($i=0;$i<count($data);$i++)
            if($i != $teacherCol) $nbMax=max($nbMax,$this->NbLines($this->widths[$i],$data[$i]));
        $h=4*$nbMax;

        //Issue a page break first if needed
        $this->CheckPageBreak($h);
        //Draw the cells of the row
        for($i=0;$i<count($data);$i++)
        {
            if($i != $teacherCol) $nb=$this->NbLines($this->widths[$i],$data[$i]);
            if($nb == 0) $nb = 1;
            if($i != $teacherCol) $w=$this->widths[$i];
            if($i != $teacherCol)$a=isset($this->aligns[$i]) ? $this->aligns[$i] : 'L';
            //Save the current position
            $x=$this->GetX();
            $y=$this->GetY();
            //set mark to red
            // if($i == 1 && is_numeric($data[$i]) && $data[$i] < $passmark) $this->SetTextColor(255, 0, 0);
            // if($i == 2 && is_numeric($data[$i]) && $data[$i] < $passmark) $this->SetTextColor(255, 0, 0);
            //Print the text
            if($i == $teacherCol ){
                $this->SetFont('Times','BI','10');
                if($data[$teacherCol-1] == "\n\t")
                    $this->Text($this->GetX() - $teacherInitialOffset, $this->GetY() + (bcdiv($h,$nb) + 1),$data[$i]);
                else $this->Text($this->GetX() - $teacherInitialOffset, $this->GetY() + (bcdiv($h,$nb) * $nbMax) - 2," ".$data[$i]);
                $this->SetFont('Times','','10');
            }else{
                if($i==count($data)-1){
                    $this->SetFont('Times','I','10');
                }
                if($i==5)  $this->MultiCell($w,bcdiv($h,$nb,1),$data[$i],$border,$a,$fill);
                else $this->MultiCell($w,bcdiv($h,$nb,1),$data[$i],$border,$a,$fill);
                $this->SetFont('Times','','10');
            }

            $this->SetTextColor(0, 0, 0);

            //Put the position to the right of the cell
            $this->SetXY($x+$w,$y);
        }
        //Go to the next line
        $this->Ln($h);
    }

    public function ReportCardRow($data, $fill=false, $border=1)
    {
        //Calculate the height of the row
        $nb=0;
        $nbMax=0;
        $noComment = false;
        $teacherCol = 9;
        $teacherInitialOffset = 62;
        $passmark = 50;
        // Teacher comment line max chars
        $maxChars = 40;

        for($i=0;$i<count($data);$i++){
            if($i != $teacherCol) {
                $nbMax=max($nbMax,$this->NbLines($this->widths[$i],$data[$i]));
            }
        }
        $h=4*$nbMax;

        //Issue a page break first if needed
        $this->CheckPageBreakReportCard($h);
        //Draw the cells of the row
        for($i=0;$i<count($data);$i++)
        {
            if($i != $teacherCol) $nb=$this->NbLines($this->widths[$i],$data[$i]);
            //if($i == $teacherCol-1) $nb = ceil(bcdiv(strlen($data[$i]), $maxChars,1));
            if($nb == 0) $nb = 1;
            if($i != $teacherCol) $w=$this->widths[$i];
            if($i != $teacherCol)$a=isset($this->aligns[$i]) ? $this->aligns[$i] : 'L';
            //Save the current position
            $x=$this->GetX();
            $y=$this->GetY();
            //set mark to red
            // if($i == 1 && is_numeric($data[$i]) && $data[$i] < $passmark) $this->SetTextColor(255, 0, 0);
            // if($i == 2 && is_numeric($data[$i]) && $data[$i] < $passmark) $this->SetTextColor(255, 0, 0);
            //Print the text
            if($i == $teacherCol ){
                $this->SetFont('Times','BI','10');
                if($data[$teacherCol-1] == "\n\t")
                    $this->Text($this->GetX() - $teacherInitialOffset, $this->GetY() + (bcdiv($h,$nb) + 1),$data[$i]);
                else $this->Text($this->GetX() - $teacherInitialOffset, $this->GetY() + (bcdiv($h,$nb) * $nbMax) - 1," ".$data[$i]);
                $this->SetFont('Times','','10');
            }else{
                if($i==count($data)-2){
                    //set teacher comments to italics
                    //$this->SetFont('Times','I','9');
                }
                // if($i==5)  $this->MultiCell($w,bcdiv($h,$nb,1),$data[$i],$border,$a,$fill);
                $this->MultiCell($w,bcdiv($h,$nb,1),$data[$i],$border,$a,$fill);
                //$this->MultiCell($w,bcdiv($h,$nb,1),"lines: ".$nb." str len:".strlen($data[$i])." nbmax: ".$nbMax,$border,$a,$fill);
                $this->SetFont('Times','','10');
            }

            $this->SetTextColor(0, 0, 0);

            //Put the position to the right of the cell
            $this->SetXY($x+$w,$y);
        }
        //Go to the next line
        $this->Ln($h);
    }

    private function CheckPageBreakReportCard($h)
    {
        //If the height h would cause an overflow, add a new page immediately
        if($this->GetY()+$h>320)
            $this->AddPage($this->CurOrientation, 'Legal');
    }

    private function CheckPageBreak($h)
    {
        //If the height h would cause an overflow, add a new page immediately
        if($this->GetY()+$h>$this->PageBreakTrigger)
            $this->AddPage($this->CurOrientation);
    }

    public function CustomPageBreakTrigger()
    {
        return $this->PageBreakTrigger;
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

    public function RotateText($txt, $angle, $cellWidth)
    {

        $x = $this->GetX();

        $y = $this->GetY();

        $yOffset = ($cellWidth / 2) - 1;

        $this->Rotate($angle, $x, $y);

        $this->Text($x - 15, $y - $yOffset, $txt);

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
