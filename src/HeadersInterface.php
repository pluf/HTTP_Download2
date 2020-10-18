<?php
namespace Pluf\Http;


/**
 * Headers Interface
 *
 * @since   7.0.0
 */
interface HeadersInterface extends CollectionInterface
{
    public function add($key, $value);

    public function normalizeKey($key);
}
