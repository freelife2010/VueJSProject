<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteRequest;
use App\Models\App;
use App\Models\AppKey;
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
            'secret'])->whereAppId($this->app->id);

        return Datatables::of($apps)
            ->edit_column('app_id', function($key) {
                return $key->app ? $key->app->name : $key->name;
            })
            ->add_column('actions', function($app) {
                return $app->getActionButtonsWithAPP('app-keys', $this->app, ['edit']);
            })
            ->make(true);
    }

    public function getCreate()
    {
        $model  = $this->app;
        $appKey = $this->app->key;
        $title  = 'Generate APP API keys';

        return view('appKeys.create', compact('model', 'title', 'appKey'));
    }

    public function postCreate(Request $request)
    {
        $this->validate($request, [
            'expire_days' => 'required|numeric'
        ]);
        $result     = $this->getResult(true, 'Could not generate APP key');
        $appKey     = new AppKey();
        $app        = App::find($request->input('app_id'));
        $expireDays = $request->input('expire_days');
        if ($appKey->generateKeys($app, $expireDays))
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
