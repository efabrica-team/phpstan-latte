<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Template\Form;

use Efabrica\PHPStanLatte\Template\NameTypeItem;
use JsonSerializable;

interface ControlInterface extends NameTypeItem, JsonSerializable
{
}
