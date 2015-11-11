<?php

namespace App\Http\Controllers;

use App\User;
use DB;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Venturecraft\Revisionable\Revisionable;
use yajra\Datatables\Datatables;

class RevisionsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getIndex()
    {
        $title     = 'Modification log';
        $subtitle  = 'See history';

        return view('revisions.index', compact('title', 'subtitle'));
    }

    public function getData()
    {
        $revisions = DB::table('revisions');

        return Datatables::of($revisions)
            ->edit_column('revisionable_type', function($revision) {
                $class = $revision->revisionable_type;
                return $class::getTableName();
            })
            ->edit_column('user_id', function($revision) {
                $user = User::find($revision->user_id);
                return $user ? "$user->email (ID: $user->id)" : '';
            })
            ->make(true);
    }
}
