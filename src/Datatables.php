<?php

namespace Raliable\DataTables;

use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\API\ResponseTrait;
use \Config\Services;
use \Config\Database;

class DataTables 
{
    use ResponseTrait;

    protected $db;
    protected $request;
    protected $builder;
    protected $column;
    protected $order = [];
    protected $joins = [];
    protected $where = [];
    protected $groupBy = null;
    protected $queryCount;

    public function __construct(Array $config)
    {
        $this->db = Database::connect($config['db'] ?? 'default');
        $this->builder = $this->db->table($config['table']);
        $this->request = Services::request();
    }

    public function select(string $columns): self
    {
        $this->column = $columns;
        return $this;
    }

    public function join(string $table, string $fk, string $type = NULL): self
    {
        $this->joins[] = [$table, $fk, $type];
        return $this;
    }

    public function where(string $keyCondition, $val = NULL): self
    {
        $this->where[] = [$keyCondition, $val, 'and'];
        return $this;
    }

    public function orWhere(string $keyCondition, $val = NULL): self
    {
        $this->where[] = [$keyCondition, $val, 'or'];
        return $this;
    }

    public function whereIn(string $keyCondition, array $val = []): self
    {
        $this->where[] = [$keyCondition, $val, 'in'];
        return $this;
    }

    public function orderBy($column, string $order = 'ASC'): self
    {
        if(is_array($column)){
            $this->order = $column;
        }else{
            $this->order[$column] = $order;
        }
        return $this;
    }

    public function groupBy(string $groupBy): self
    {
        $this->groupBy = $groupBy;
        return $this;
    }

    protected function _getDatatablesQuery(): void
    {
        $searchValue = $this->request->getPost('search')['value'] ?? '';
        $columns = $this->request->getPost('columns');

        $this->builder->select($this->column);

        foreach($this->joins as $join){
            $this->builder->join($join[0], $join[1], $join[2]);
        }

        foreach($this->where as $condition){
            [$key,  $val, $type] = $condition;
            if ($type === 'and') {
                $this->builder->where($key, $val);
            } elseif ($type === 'or') {
                $this->builder->orWhere($key, $val);
            } elseif ($type === 'in') {
                $this->builder->whereIn($key, $val);
            }
        }

        if ($this->groupBy) {
            $this->builder->groupBy($this->groupBy);
        }

        if ($searchValue) {
            $this->builder->groupStart();
            foreach ($columns as $idx => $column) {
                if ($column['searchable'] === true && !empty($column['name'])) {
                    $this->builder->orLike($column['name'], $searchValue);
                }
            }
            $this->builder->groupEnd();
        }

        $order = $this->request->getPost('order');
        if (isset($order)) {
            $orderColumn = $columns[$order[0]['column']]['data'];
            $orderDir = $order[0]['dir'];
            $this->builder->orderBy($orderColumn, $orderDir);
        } elseif ($this->order) {
            foreach ($this->order as $key => $val) {
                $this->builder->orderBy($key, $val);
            }
        }
    }

    protected function getDatatables(): array
    {
        $this->_getDatatablesQuery();
        if ($this->request->getPost('length') != -1) {
            $this->builder->limit($this->request->getPost('length'), $this->request->getPost('start'));
        }
        return $this->builder->get()->getResultArray();
    }

    public function countTotal(): array
    {
        $this->_getDatatablesQuery();
        $totalFiltered = $this->builder->countAllResults(false);
        $totalAll = $this->builder->countAllResults();

        return ['total_all' => $totalAll, 'total_filtered' => $totalFiltered];
    }

    public function generate(bool $raw = false)
    {
        $list = $this->getDatatables();
        $total = $this->countTotal();
        $data = [];
        $no = $this->request->getPost('start');

        foreach ($list as $val) {
            $no++;
            $val['no'] = $no;
            $data[] = $val;
        }

        $output = [
            'draw' => $this->request->getPost('draw'),
            'recordsTotal' => $total['total_all'],
            'recordsFiltered' => $total['total_filtered'],
            'data' => $data,
            csrf_token() => csrf_hash(),
        ];

        if ($raw) {
            return $output;
        } else {
            return $this->respond($output);
        }
    }

}
