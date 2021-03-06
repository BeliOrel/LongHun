<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Support\Facades\Hash;
use Intervention\Image\ImageManagerStatic as Image;

class UserController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // authentication for api
        $this->middleware('auth:api');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // $this->authorize('isAdmin');
        if (\Gate::allows('isAdmin') || \Gate::allows('isAuthor')) {
            return User::latest()->paginate(5);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|string|max:190',
            'email' => 'required|email|max:190|unique:users',
            'password' => 'required|string|min:6|max:190'
        ]);
        return User::create([
            'name' => $request['name'],
            'email' => $request['email'],
            'type' => $request['type'],
            'bio' => $request['bio'],
            'photo' => $request['photo'],
            'password' => Hash::make($request['password'])
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $this->validate($request, [
            'name' => 'required|string|max:190',
            // this email is already in DB, so we need to escape it
            'email' => 'required|email|max:190|unique:users,email,'.$user->id,
            'password' => 'sometimes|string|min:6|max:190'
        ]);

        $user->update($request->all());
        return ['message' => 'Updated'];
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        // only admin can use this
        $this->authorize('isAdmin');
        $user = User::findOrFail($id);

        // delete user
        $user->delete();

        return ['message' => 'User Deleted'];
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function profile()
    {
        return auth('api')->user();
    }

    /**
     * Display the specified resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateProfile(Request $request)
    {
        $user = auth('api')->user();

        $this->validate($request, [
            'name' => 'required|string|max:190',
            // this email is already in DB, so we need to escape it
            // it's required but not for the currently logged user
            'email' => 'required|email|max:190|unique:users,email,'.$user->id,
            'password' => 'sometimes|string|min:6|max:190'
        ]);

        $currentPhoto = $user->photo;
        
        // check if it's the same image as in DB (name)
        if($request->photo != $currentPhoto) {
            // extract the extension from the request data of photo
            $name = time().'.' . explode('/', explode(':', substr($request->photo, 0, strpos($request->photo, ';')))[1])[1];
            Image::make($request->photo)->save(public_path('img/profile/').$name);

            $request->merge(['photo' => $name]);

            $userPhoto = public_path('img/profile/').$currentPhoto;

            // delete old image 
            if(file_exists($userPhoto)){
                @unlink($userPhoto);
            }
        }

        if(!empty($request->password)){
            $request->merge(['password' => Hash::make($request['password'])]);
        }

        $user->update($request->all());        
        return ['message' => 'Profile update successful'];
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function search()
    {
        if($search = \Request::get('q')){
            $users = User::where(function($query) use ($search){
                $query->where('name', 'LIKE', "%$search%")->orWhere('email', 'LIKE', "%$search%");
            })->paginate(10);
        }
        else {
            // if query is empty
            // just return some users
            $users = User::latest()->paginate(5);
        }
        return $users;
    }
}
