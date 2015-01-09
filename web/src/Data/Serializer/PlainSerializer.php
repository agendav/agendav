<?php
namespace AgenDAV\Data\Serializer;

use League\Fractal\Pagination\CursorInterface;
use League\Fractal\Pagination\PaginatorInterface;
use League\Fractal\Serializer\ArraySerializer;

class PlainSerializer extends ArraySerializer
{
    /**
     * Serialize a collection
     *
     * @param  string  $resourceKey
     * @param  array  $data
     * @return array
     **/
    public function collection($resourceKey, array $data)
    {
        return $data;
    }
}
