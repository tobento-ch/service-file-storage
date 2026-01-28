<?php

/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

declare(strict_types=1);

namespace Tobento\Service\FileStorage\Repository;

use Tobento\Service\Repository\Storage\Column\AbstractColumn;

final class StreamColumn extends AbstractColumn
{
    /**
     * Create a new instance.
     *
     * @param string $name
     */
    public function __construct(
        protected string $name,
    ) {
        $this->type(type: 'string');
    }
    
    /**
     * Read value. Might be used for casting.
     *
     * @param mixed $value
     * @param array $attributes
     * @return mixed
     */
    public function reading(mixed $value, array $attributes): mixed
    {
        return $value;
    }
    
    /**
     * Write value. Might be used for casting.
     *
     * @param mixed $value
     * @param array $attributes
     * @param string $action The action name that was performed such as 'create' or 'update'.
     * @return mixed
     */
    public function writing(mixed $value, array $attributes, string $action = ''): mixed
    {
        return $value;
    }
}