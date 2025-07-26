<?php

namespace App\Http\Controllers\Sales;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Spatie\SimpleExcel\SimpleExcelReader;
use App\Models\Contact;

class ContactImportController extends Controller
{
    public function showForm()
    {
        return view('sales.contacts.import');
    }

    public function import(Request $request)
    {
        $request->validate([
            'contacts_file' => 'required|file|mimes:xlsx,csv',
        ]);

        $uploadedFile = $request->file('contacts_file');
        $originalExtension = $uploadedFile->getClientOriginalExtension();
        $tmpPath = $uploadedFile->getPathname();
        $renamedPath = $tmpPath . '.' . $originalExtension;

        copy($tmpPath, $renamedPath);

        SimpleExcelReader::create($renamedPath)->getRows()->each(function(array $row) {
            // اگر ایمیل وجود داره و تکراریه، رد کن
            if (!empty($row['email']) && Contact::where('email', $row['email'])->exists()) {
                return;
            }

            Contact::create([
                'first_name' => $row['first_name'] ?? null,
                'last_name'  => $row['last_name'] ?? null,
                'email'      => $row['email'] ?? null,
                'phone'      => $row['phone'] ?? null,
                'mobile'     => $row['mobile'] ?? null,
                'company'    => $row['company'] ?? null,
                'city'       => $row['city'] ?? null,
            ]);
        });

        return redirect()->route('sales.contacts.index')
                         ->with('success', 'مخاطبین با موفقیت ایمپورت شدند.');
    }
}
