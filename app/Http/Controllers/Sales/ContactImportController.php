<?php

namespace App\Http\Controllers\Sales;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ContactsImport;

class ContactImportController extends \App\Http\Controllers\Controller
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

        Excel::import(new ContactsImport, $request->file('contacts_file'));

        return redirect()->route('sales.contacts.index')->with('success', 'مخاطبین با موفقیت ایمپورت شدند.');
    }
}
