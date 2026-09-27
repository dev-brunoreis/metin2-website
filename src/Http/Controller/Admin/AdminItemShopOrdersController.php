<?php

declare(strict_types=1);

namespace Metin2Website\Http\Controller\Admin;

use Metin2Website\Admin\AdminPaths;
use Metin2Website\Http\Response;

class AdminItemShopOrdersController extends AdminItemShopBaseController
{
    public function ordersIndex(): Response
    {
        return $this->redirect(AdminPaths::store('orders'));
    }
}
