<?php

return [
    /*
     * Map model to transformer
     */
    'transformers' => [
        \App\Base\Validator::class => \Modules\App\Transformers\ValidatorTransformer::class,
        \Modules\User\Models\User::class => \Modules\User\Transformers\UserTransformer::class,
        \Modules\Application\Model\Application::class => \Modules\Application\Transformers\ApplicationTransformer::class,
        \Modules\ShippingPartner\Services\ShippingPartnerProviderInterface::class => \Modules\ShippingPartner\Transformers\ShippingPartnerProviderTransformer::class,
    ],

    /*
     * The transformer finder list
     */
    'transformer_finders' => [
    ],
];
