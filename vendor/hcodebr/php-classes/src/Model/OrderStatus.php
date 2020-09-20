<?php

namespace Hcode\Model;

use Hcode\DB\Sql;
use Hcode\Model;

class OrderStatus extends Model
{
    public const EM_ABERTO = 1;
    public const AGUARDANDO_PAGAMENTO = 2;
    public const PAGO = 3;
    public const ENTREGUE = 4;
}
