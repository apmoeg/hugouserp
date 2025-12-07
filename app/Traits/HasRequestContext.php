<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

trait HasRequestContext
{
    protected function request(): ?Request
    {
        /** @var Request|null $req */
        $req = app()->bound('request') ? app('request') : null;

        return $req instanceof Request ? $req : null;
    }

    public function currentUser(): ?Authenticatable
    {
        return Auth::guard('api')->user() ?? Auth::user();
    }

    public function currentBranchId(): ?int
    {
        $req = $this->request();

        if ($req && $req->attributes->has('branch_id')) {
            $id = $req->attributes->get('branch_id');

            return $id !== null ? (int) $id : null;
        }

        if (app()->has('req.branch_id')) {
            $id = app('req.branch_id');

            return $id !== null ? (int) $id : null;
        }

        return null;
    }

    public function currentBranch()
    {
        $req = $this->request();

        if ($req && $req->attributes->has('branch')) {
            return $req->attributes->get('branch');
        }

        if (app()->has('req.branch')) {
            return app('req.branch');
        }

        return null;
    }

    public function currentModuleKey(): ?string
    {
        $req = $this->request();

        if ($req && $req->attributes->has('module_key')) {
            return (string) $req->attributes->get('module_key');
        }

        if (app()->has('req.module_key')) {
            return (string) app('req.module_key');
        }

        return null;
    }
}
