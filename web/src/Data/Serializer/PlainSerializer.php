<?php
namespace AgenDAV\Data\Serializer;

use League\Fractal\Pagination\CursorInterface;
use League\Fractal\Pagination\PaginatorInterface;
use League\Fractal\Serializer\ArraySerializer;

class PlainSerializer extends ArraySerializer
{
    public function collection(?string $resourceKey, array $data): array
    {
        return $data;
    }
}
