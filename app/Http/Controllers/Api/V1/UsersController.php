<?php

namespace App\Http\Controllers\Api\V1;

use Validator;
use Illuminate\Http\Request;
use App\Models\Access\User\User;
use App\Http\Resources\UserResource;
use App\Events\Backend\Access\User\UserCreated;
use App\Events\Backend\Access\User\UserUpdated;
use App\Repositories\Backend\Access\User\UserRepository;

class UsersController extends APIController
{
    protected $repository;

    /**
     * __construct.
     *
     * @param $repository
     */
    public function __construct(UserRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Return the users.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $limit = $request->get('paginate') ? $request->get('paginate') : 25;

        return UserResource::collection(
            $this->repository->getForDataTable(1, false)->paginate($limit)
        );
    }

    /**
     * Return the specified resource.
     *
     * @param User $user
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(User $user)
    {
        return new UserResource($user);
    }

     /**
     * Create User
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validation = $this->validateUser($request);

        if ($validation->fails()) {
            return $this->throwValidation($validation->messages()->first());
        }

        $this->repository->create($request);

        event(new UserCreated($user));

        return new UserResource(User::orderBy('created_at', 'desc')->first());
    }

    /**
     * Update User
     *
     * @param Request $request
     * @param User $user
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, User $user)
    {
        $validation = $this->validateUser($request, 'edit', $user->id);

        if ($validation->fails()) {
            return $this->throwValidation($validation->messages()->first());
        }

        $updatedUser = $this->repository->update($user, $request);

        event(new UserUpdated($user));

        return new UserResource($updatedUser);
    }

    /**
     * Delete User
     *
     * @param User    $user
     * @param Request $request
     *
     * @return mixed
     */
    public function destroy(User $user, Request $request)
    {
        $this->repository->delete($user);

        return $this->respond([
            'message'   => trans('alerts.backend.users.deleted'),
        ]);
    }

    /**
     * Return the deactivate users
     *
     * @param Request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function deactivatedUserList(Request $request)
    {
        $limit = $request->get('paginate') ? $request->get('paginate') : 25;

        return UserResource::collection(
            $this->repository->getForDataTable(0, false)->paginate($limit)
        );
    }

    /**
     * Return the deleted users.
     *
     * @param User $user
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteUserList(Request $request)
    {
        $limit = $request->get('paginate') ? $request->get('paginate') : 25;

        return UserResource::collection(
            $this->repository->getForDataTable(0, true)->paginate($limit)
        );
    }

    /**
     * validateUser User
     *
     * @param $request
     * @param $action
     * @param $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function validateUser(Request $request, $action = '', $id = 0)
    {
        $password = ($action == 'edit') ? '' : 'required|min:6|confirmed';

        $validation = Validator::make($request->all(), [
            'first_name'      => 'required|max:255',
            'last_name'       => 'required|max:255',
            'email'           => 'required|max:255|email|unique:users,email,'.$id,
            'password'        => $password,
            'assignees_roles' => 'required',
            'permissions'     => 'required',
        ]);

        return $validation;
    }
}
