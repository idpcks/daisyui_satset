<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest\BulkUpdateUserStatusRequest;
use App\Http\Requests\UserRequest\StoreUserRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Auth\Access\Response as AuthorizationResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class UsersController extends Controller
{
    public function __construct(
        private readonly UserService $users,
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $filters = [
            'search' => $request->input('search'),
            'status' => $request->input('status'),
        ];

        $actor = $request->user();
        $isImpersonating = $request->session()->has(ImpersonationController::SESSION_IMPERSONATOR_ID);

        $users = $this->users->paginateUsers($filters, $actor, $isImpersonating);

        return Inertia::render('users/Index', [
            'users' => $users,
            'filters' => $filters,
            'roles' => $this->users->assignableRoleNames($request->user()),
        ]);
    }

    /**
     * Aktifkan atau nonaktifkan beberapa pengguna sekaligus.
     */
    public function bulkStatus(BulkUpdateUserStatusRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $actor = $request->user();
        $isActive = (bool) $data['is_active'];

        $denied = User::query()
            ->whereIn('id', $data['ids'])
            ->get()
            ->map(fn (User $user): AuthorizationResponse => Gate::inspect(
                $isActive ? 'update' : 'deactivate',
                $user,
            ))
            ->first(fn (AuthorizationResponse $authorization): bool => $authorization->denied());

        if ($denied) {
            return back()->withErrors(['ids' => $denied->message()]);
        }

        $updated = $this->users->bulkUpdateStatus($data['ids'], $isActive, $actor);

        $status = $isActive ? __('messages.users.activated') : __('messages.users.deactivated');

        return back()->with('success', __('messages.users.bulk_updated', ['count' => $updated, 'status' => $status]));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $result = $this->users->createUser($data, $request->user());

        $flash = ['success' => __('messages.users.created')];

        if ($result['password'] !== null) {
            $flash['generated_password'] = $result['password'];
            $flash['generated_for'] = $result['user']->username;
            $flash['magic_link_sent'] = $result['magic_link_sent'];
        }

        return to_route('users.index')->with($flash);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();
        $actor = $request->user();

        $authorization = Gate::inspect('update', $user);

        if ($authorization->denied()) {
            return back()->with('error', $authorization->message());
        }

        if (! ($data['is_active'] ?? true)) {
            $authorization = Gate::inspect('deactivate', $user);

            if ($authorization->denied()) {
                return back()->withErrors(['is_active' => $authorization->message()]);
            }
        }

        $role = $data['role'] ?? null;

        if ($role !== null) {
            $authorization = Gate::inspect('changeRole', [$user, $role]);

            if ($authorization->denied()) {
                return back()->withErrors(['role' => $authorization->message()]);
            }
        }

        $this->users->updateUser($user, $data, $actor);

        return to_route('users.index')->with('success', __('messages.users.updated'));
    }

    /**
     * Reset password pengguna dan kirim tautan aman ke emailnya.
     */
    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $authorization = Gate::inspect('update', $user);

        if ($authorization->denied()) {
            return back()->with('error', $authorization->message());
        }

        $result = $this->users->resetPassword($user, $request->user());

        $flash = ['success' => __('messages.users.password_reset')];

        $flash['generated_password'] = $result['password'];
        $flash['generated_for'] = $result['user']->username;
        $flash['magic_link_sent'] = $result['magic_link_sent'];

        return back()->with($flash);
    }

    /**
     * Kirim ulang tautan aman untuk mengatur password.
     */
    public function resendPasswordLink(Request $request, User $user): RedirectResponse
    {
        $authorization = Gate::inspect('update', $user);

        if ($authorization->denied()) {
            return back()->with('error', $authorization->message());
        }

        $sent = $this->users->sendPasswordLink($user);

        return back()->with(
            $sent ? 'success' : 'error',
            $sent
                ? __('messages.users.password_link_sent')
                : __('messages.users.password_link_failed'),
        );
    }

    /**
     * Tandai email pengguna sebagai terverifikasi secara manual.
     */
    public function verifyEmail(Request $request, User $user): RedirectResponse
    {
        $authorization = Gate::inspect('update', $user);

        if ($authorization->denied()) {
            return back()->with('error', $authorization->message());
        }

        $verified = $this->users->markEmailVerified($user, $request->user());

        return back()->with(
            $verified ? 'success' : 'info',
            $verified
                ? __('messages.users.email_verified')
                : __('messages.users.email_already_verified'),
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        $authorization = Gate::inspect('delete', $user);

        if ($authorization->denied()) {
            return back()->with('error', $authorization->message());
        }

        $this->users->deleteUser($user, $request->user());

        return to_route('users.index')->with('success', __('messages.users.deleted'));
    }
}
