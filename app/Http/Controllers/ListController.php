<?php

namespace App\Http\Controllers;

use App\Enums\ListType;
use App\ListItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Laravel\Lumen\Routing\Controller;
use Illuminate\Database\Query\Builder;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ListController extends Controller
{
    protected $tableName = 'materials';
    protected $idColumn = 'material';
    protected $idFilterName = 'material_ids';

    public function get(Request $request, ListType $type): array
    {
        return [
            'id' => $type,
            'materials' => $this->getItems($request, $type),
        ];
    }

    protected function getItems(Request $request, ListType $type): Collection
    {
        $query = DB::table($this->tableName)
            ->where(['guid' => $request->user()->getId(), 'list' => $type]);

        // Filter to the given items, if supplied.
        $itemIds = $this->commaSeparatedQueryParamToArray($this->idFilterName, $request);
        if (count($itemIds)) {
            // The OpenAPI spec defines the parameter as a comma separated
            // list. OpenAPI defaults to using "id=1&id=2" for array types,
            // but PHP expects "id[]=1&id[]=2". So rather than trying to hack
            // around that, we use the other common option of using a single
            // comma separated value and just split it up here. Looks nicer in
            // the URL.
            $query->where(function ($query) use ($itemIds) {
                foreach ($itemIds as $itemId) {
                    $item = ListItem::createFromUrlParameter($itemId);
                    $this->idQuery($query, $item, true);
                }
            });
        }

        $items = $query->orderBy('changed_at', 'DESC')
            ->select($this->idColumn)
            ->pluck($this->idColumn);

        return $items;
    }

    public function itemAvailability(Request $request, ListType $type, ListItem $item): Response
    {
        $count = $this->idQuery(DB::table($this->tableName), $item)
            ->where(['guid' => $request->user()->getId(), 'list' => $type])
            ->count();

        if ($count > 0) {
            return new Response('', 200);
        } else {
            throw new NotFoundHttpException('No such item');
        }
    }

    public function addItem(Request $request, ListType $type, ListItem $item): Response
    {
        $this->idQuery(DB::table($this->tableName), $item)
            ->where([
                'guid' => $request->user()->getId(),
                'list' => $type,
            ])
            ->delete();

        DB::table($this->tableName)
            ->insert([
                'guid' => $request->user()->getId(),
                'list' => $type,
                $this->idColumn => urldecode($item->fullId),
                // We need to format the date ourselves to add microseconds.
                'changed_at' => Carbon::now()->format('Y-m-d H:i:s.u'),
            ]);

        return new Response('', 201);
    }

    public function removeItem(Request $request, ListType $type, ListItem $item): Response
    {
        $count = $this->idQuery(DB::table($this->tableName), $item)
            ->where([
                'guid' => $request->user()->getId(),
                'list' => $type,
            ])
            ->delete();

        return new Response('', $count > 0 ? 204 : 404);
    }

    /**
     * Create a query that deals properly with basis/katalog items.
     */
    protected function idQuery(Builder $query, ListItem $item, $useOr = true): Builder
    {
        $idWhere = [$this->idColumn, '=', $item->agency . '-' . $item->base . ':' . $item->id];

        if (\in_array($item->base, ['katalog', 'basis'])) {
            $idWhere = [$this->idColumn, 'LIKE', '%:' . $item->id];
        }

        return $useOr ? $query->orWhere([$idWhere]) : $query->where([$idWhere]);
    }

    protected function commaSeparatedQueryParamToArray(string $param, Request $request): array
    {
        if ($request->has($param)) {
            return explode(',', $request->get($param)) ?? [];
        }

        return [];
    }
}
