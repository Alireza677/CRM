<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\SimpleExcel\SimpleExcelWriter;
use App\Models\Lead;

class LeadExportController extends Controller
{
    
    public function export(Request $request, $format = null)
    {
        $format = strtolower($format ?? $request->get('format', 'csv'));
        $format = in_array($format, ['csv','xlsx']) ? $format : 'csv';
    
        // جدول درست
        $table = 'sales_leads';
    
        // بیس کوئری
        $query = \DB::table($table)->select('id','full_name','mobile')->orderBy('id');
    
        // فایل خروجی
        $filename = 'leads-'.now()->format('Ymd-His').'.'.$format;
        $tmpDir   = storage_path('app/tmp');
        if (!is_dir($tmpDir)) @mkdir($tmpDir, 0775, true);
        $tmpPath  = $tmpDir.'/'.$filename;
    
        $writer = \Spatie\SimpleExcel\SimpleExcelWriter::create($tmpPath, $format)
            ->addHeader(['Name', 'Mobile']);
    
        foreach ($query->cursor() as $row) {
            $writer->addRow([
                $row->full_name ?? '',
                $row->mobile ?? '',
            ]);
        }
    
        $writer->close();
        return response()->download($tmpPath, $filename)->deleteFileAfterSend(true);
    }
    


}
