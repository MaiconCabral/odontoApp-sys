<?php

namespace App\Domains\Tenancy\Support;

use App\Domains\Tenancy\Models\Tenant;

// Links always come from the tenant record, never from the request host, which a client can forge.
class ClinicUrl
{
    /**
     * Builds a frontend URL on the clinic subdomain.
     *
     * @param  array<string, string>  $query
     */
    public static function to(Tenant $tenant, string $path = '/', array $query = []): string
    {
        $centralDomain = config('tenancy.central_domains')[0];
        $host = "{$tenant->slug()}.{$centralDomain}";
        $url = str_replace('{host}', $host, config('app.frontend_url_template')).$path;

        return $query ? $url.'?'.http_build_query($query) : $url;
    }
}
