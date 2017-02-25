<?php namespace talenthub\Http\Controllers\ManagerApp;

use Illuminate\Support\Facades\Auth;
use talenthub\Http\Requests;
use talenthub\Http\Controllers\Controller;

use Illuminate\Http\Request;

class ManagerProfileController extends Controller {

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{
		return response()->view('manager_app/app')->withCookie(cookie('unsigned::test_data','test/data',10,null,null,false, false));
	}

}
