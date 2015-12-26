<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteRequest;
use App\Models\App;
use App\Models\AppKey;
use DB;
use Illuminate\Http\Request;

use App\Http\Requests;
use URL;
use yajra\Datatables\Datatables;

class AppKeysController extends AppBaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getIndex()
    {
        $APP      = $this->app;
        $title    = $APP->name . ': API keys';
        $subtitle = 'Manage API keys';

        return view('appKeys.index', compact('title', 'subtitle', 'APP'));
    }

    public function getData()
    {
        $apps = AppKey::select([
            'id',
            'app_id',
            'expire_time',
            'secret',
            'created_at'])->whereAppId($this->app->id);

        return Datatables::of($apps)
            ->edit_column('app_id', function($key) {
                return $key->app ? $key->app->name : $key->name;
            })
            ->add_column('status', function($app) {
                return $app->isExpired() ? 'Expired' : 'Active';
            })
            ->add_column('scopes', function($app) {
                return $app->getScopes();
            })
            ->edit_column('created_at', function($app) {
                return $app->created_at->format('d.m.Y H:i:s');
            })
            ->add_column('actions', function($app) {
                return $app->getActionButtonsWithAPP('app-keys', $this->app, ['edit']);
            })
            ->make(true);
    }

    public function getCreate()
    {
        $model  = $this->app;
        $title  = 'Generate APP API keys';
        $scopes = DB::table('oauth_scopes')->lists('description', 'id');

        return view('appKeys.create', compact('model', 'title', 'scopes'));
    }

    public function postCreate(Request $request)
    {
        $this->validate($request, [
            'scopes'      => 'required',
            'expire_days' => 'required|numeric'
        ]);
        $result     = $this->getResult(true, 'Could not generate APP key');
        $appKey     = new AppKey();
        $app        = App::find($request->input('app_id'));
        $expireDays = $request->input('expire_days');
        if ($appKey->generateKeys($app, $expireDays, $request->scopes))
            $result = $this->getResult(false, 'App key has been generated');

        return $result;
    }

    public function getDelete($id)
    {
        $title = 'Delete APP key ?';
        $model = AppKey::find($id);
        $APP   = $this->app;
        $url   = Url::to('app-keys/delete/'.$model->id);
        return view('appKeys.delete', compact('title', 'model', 'APP', 'url'));
    }

    public function postDelete(DeleteRequest $request, $id)
    {
        $result = $this->getResult(true, 'Could not delete APP key');
        $model  = AppKey::find($id);
        if ($model->delete()) {
            $result = $this->getResult(false, 'APP key deleted');
        }

        return $result;
    }
}
