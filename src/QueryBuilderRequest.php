<?php
namespace Ningwei\QueryBuild;
use Illuminate\Http\Request;

class QueryBuilderRequest extends Request
{
    /**
     * @param Request $request
     * @return static
     */
    static function fromRequest(Request $request): self {
        return static::createFrom($request, new self());
    }
}