<?php

namespace App\Services;

use App\Models\EligibleDomain;
use App\Models\Product;

class EligibilityService
{
    public function check(string $email): array
    {
        $domain = strtolower(substr(strrchr($email, '@'), 1));

        $eligible = EligibleDomain::isEligible($domain);

        $products = $eligible
            ? Product::active()->get()
            : collect();

        return [
            'email'    => $email,
            'domain'   => $domain,
            'eligible' => $eligible,
            'products' => $products,
        ];
    }
}
