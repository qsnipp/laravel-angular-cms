<?php namespace App\Http\Controllers\Api;

use Input;
use Cache;
use Response;
use Validator;
use Config;

use App\Models\Role;
use App\Transformers\RoleTransformer;

use App\Exceptions\NotFoundException;
use App\Exceptions\ResourceException;

class RoleController extends ApiController
{

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $validator = Validator::make(Input::all(), [
            'ids'            => 'array|integerInArray',
            'page'           => 'integer',
            'created_at_min' => 'date_format:"Y-m-d H:i:s"',
            'created_at_max' => 'date_format:"Y-m-d H:i:s"',
            'updated_at_min' => 'date_format:"Y-m-d H:i:s"',
            'updated_at_max' => 'date_format:"Y-m-d H:i:s"',
            'limit'          => 'integer|min:1|max:250',
            'search'         => 'string'
        ]);
        if ($validator->fails()) {
            throw new ResourceException($validator->errors()->first());
        }

        $roles = new Role;
        //Filter
        if (Input::has('search')) {
            $roles = $roles->where('display_name', 'LIKE', '%' . Input::get('search') . '%');
        }
        $roles = $roles->simplePaginate(Input::get('limit', 50));

        return response()->paginator($roles, new RoleTransformer);

    }


    /**
     * Display the specified resource.
     *
     * @param  int $id
     *
     * @return Response
     */
    public function show($id)
    {

        $role = Role::find($id);
        if (is_null($role)) {
            throw new NotFoundException;
        }

        return response()->item($role, new RoleTransformer);

    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store()
    {
        $rules = [
            'name'         => 'required|alpha_dash|min:1|max:255',
            'display_name' => 'string|max:255',
            'description'  => 'string',
            'permissions'  => 'array|integerInArray|existsInArray:permission,id',
        ];

        $validator = Validator::make(Input::only(array_keys($rules)), $rules);

        if ($validator->fails()) {
            throw new ResourceException($validator->errors()->first());
        }
        $role = new Role;

        $fields = ['name'];
        foreach ($fields as $key => $field) {
            if (Input::has($field)) {
                $role->{$field} = Input::get($field);
            }
        }

        //field which can null/empty string
        $fields = ['description', 'display_name'];
        foreach ($fields as $key => $field) {
            if (Input::get($field) === '') {
                $role->{$field} = null;
            } elseif (Input::has($field)) {
                $role->{$field} = Input::get($field);
            }
        }
        $role->save();

        $role->perms()->sync(Input::get('permissions', []));

        return $this->show($role->id);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int $id
     *
     * @return Response
     */
    public function update($id)
    {
        $rules = [
            'name'         => 'alpha_dash|min:1|max:255',
            'display_name' => 'string|max:255',
            'description'  => 'string',
            'permissions'  => 'array|integerInArray|existsInArray:permission,id',
        ];

        $validator = Validator::make(Input::only(array_keys($rules)), $rules);

        if ($validator->fails()) {
            throw new ResourceException($validator->errors()->first());
        }
        $role = Role::find($id);
        if (is_null($role)) {
            throw new NotFoundException;
        }

        $fields = ['name'];
        foreach ($fields as $key => $field) {
            if (Input::has($field)) {
                $role->{$field} = Input::get($field);
            }
        }

        //field which can null/empty string
        $fields = ['description', 'display_name'];
        foreach ($fields as $key => $field) {
            if (Input::get($field) === '') {
                $role->{$field} = null;
            } elseif (Input::has($field)) {
                $role->{$field} = Input::get($field);
            }
        }
        $role->save();
        if (Input::has('permissions')) {
            $role->perms()->sync(Input::get('permissions', []));
        }

        return $this->show($role->id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $role = Role::find($id);
        if (is_null($role)) {
            throw new NotFoundException;
        }

        $role->delete();

        return response()->return();
    }

}