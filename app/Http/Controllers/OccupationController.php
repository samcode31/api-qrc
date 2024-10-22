<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;

class OccupationController extends Controller
{
    public function show(Request $request) 
    {
        $parent = $request->input('parent');
        $data = array();

        $occupations = Student::whereBetween('class_id', ['1%', '7%'])
        ->select("$parent as text")
        ->distinct()
        ->get();

        foreach($occupations as $index => $occupation)
        {
            $occupationClassIds = Student::where($parent, $occupation->text)
            ->whereBetween('class_id', ['1', '7'])
            ->select('class_id')
            ->distinct()
            ->get()
            ->pluck('class_id')
            ->toArray();

            $occupation->id = $index;
            $occupation->class_ids = [];

            foreach ($occupationClassIds as $classId) {
                $count = Student::where($parent, $occupation->text)
                ->where('class_id', $classId)
                ->count();

                $occupation->class_ids = [
                    'class_id' => $classId,
                    'count' => $count
                ];
            }

            $data[] = $occupation;
        }
        return $data;
    }
}
