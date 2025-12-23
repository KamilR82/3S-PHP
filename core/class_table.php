<?php

declare(strict_types = 1);

//table elements must be in the following context:
//<table><caption> <colgroup><col> <thead><tr><th> <tbody><tr><td> <tfoot><tr><td>

class Table extends Element
{
	private array $columns = []; //column names index map

	private ?object $caption = null;
	private ?object $colgroup = null;
	private ?object $thead = null;
	private ?object $tbody = null;
	private ?object $tfoot = null;

	private bool $sort_mark = true; //show sort mark
	private ?object $sort_asc = null; //sort mark up
	private ?object $sort_desc = null; //sort mark down
	private string $sort_table = 'table_sort'; //URI param key
	private ?string $sort_column = null; //URI param value

	public function __construct(?string $caption = null, mixed ...$attrib)
	{
		parent::__construct(strtolower(__CLASS__), false, $attrib);
		//caption
		if(!is_null($caption)) $this->open($this->caption = new Element('caption', $caption));
		//sort mark
		$this->sort_asc = new Element('span', '&#9650;'); //, ['style'=>'float: left/right;']
		$this->sort_desc = new Element('span', '&#9660;'); //, ['style'=>'float: left/right;']
		//sort column
		if(isset($attrib['id'])) $this->sort_table .= '_'.$attrib['id']; //get table id for sort
		$this->sort_column = Request::GetParam($this->sort_table, true); //try get value from params
	}

	public function sort(?string $column = null): ?string
	{
		if(!is_null($column)) $this->sort_column = $column;
		return $this->sort_column;
	}

	public function mark(bool $mark = true): void
	{
		$this->sort_mark = $mark;
	}

	public function marks(object $ascending, object $descending): void
	{
		unset($this->sort_asc);
		$this->sort_asc = $ascending;
		unset($this->sort_desc);
		$this->sort_desc = $descending;
	}

	public function caption(?string $caption = null, mixed ...$attrib): void
	{
		if(is_null($caption)) $this->remove('caption'); //remove
		else //set
		{
			if($this->caption)
			{
				$this->caption->clear();
				$this->caption->add($caption);
				$this->caption->attrib($attrib);
			}
			else $this->open($this->caption = new Element('caption', $caption, $attrib));
		}
	}

	public function clear(bool $head = false, bool $body = true, bool $foot = true): void //only data by default
	{
		if($head)
		{
			if($this->colgroup) $this->colgroup->clear();
			if($this->thead) $this->thead->clear();
			$this->columns = [];
		}
		if($body && $this->tbody) $this->tbody->clear();
		if($foot && $this->tfoot) $this->tfoot->clear();
	}

//add

	public function colgroup(?int $span = null, ?string $class = null): void
	{
		if(!$this->colgroup)
		{
			$this->colgroup = new Element('colgroup');
			$this->open($this->colgroup);
			$this->close('colgroup');
		}
		$this->colgroup->open(new Element('col', ['span' => $span, 'class' => $class]));
	}

	public function head(array $values): void
	{
		//open head
		if(!$this->thead)
		{
			$this->thead = new Element('thead');
			$this->open($this->thead);
			$this->close('thead');
		}
		else $this->thead->activate(); //close prev row (or any if is open)

		//add row
		$this->thead->open(new Element('tr', true));
		
		//add data
		$index = 0; //counter
		foreach($values as $name => $value)
		{
			if(is_string($name)) $this->columns[$name] = $index++; //column names index map

			if(is_array($value)) //sortable
			{
				$mark = null;
				$label = $value['label'] ?? $value['l'] ?? $value[0] ?? ''; //label
				$column = $value['column'] ?? $value['c'] ?? $value[1] ?? ''; //column name
				$attrib = $value['attrib'] ?? $value['a'] ?? $value[2] ?? ''; //attributes
				if(!is_array($attrib)) $attrib = null; //no attributes
				if(Str::IsEmpty($column)) $column = $label;
				//arrow
				if($this->sort_column && strcasecmp($this->sort_column, $column) == 0) //active sort on this column?
				{
					if(Str::IsCapitalLetter($this->sort_column))
					{
						if($this->sort_mark) $mark = $this->sort_desc;
						$column = lcfirst($column); //set first char to lowercase
					}
					else
					{
						if($this->sort_mark) $mark = $this->sort_asc;
						$column = ucfirst($column); //set first char to uppercase
					}
				}
				$this->thead->open(new Element('th', $attrib, $mark, new Element('a', $label, href: Request::Modify([$this->sort_table => $column]))));
			}
			else $this->thead->open(new Element('th', $value)); //only string
		}
	}

	public function body(array $values): void
	{
		//open body
		if(!$this->tbody)
		{
			$this->tbody = new Element('tbody');
			$this->open($this->tbody);
			$this->close('tbody');
		}
		else $this->tbody->activate(); //close prev row (or any if is open)

		//add row
		$this->tbody->open(new Element('tr', true));
		
		//add data
		if(array_is_list($values)) foreach($values as $value) $this->tbody->open(new Element('td', $value));
		else
		{
			$row = [];
			foreach($values as $key => $val)
			{
				if (is_string($key)) //set by name
				{
					if(isset($this->columns[$key])) $row[$this->columns[$key]] = $val; //key exists (replace)
					elseif(count($this->columns) === 0) $row[] = $val; //add (only if map is empty)
				}
				elseif (is_int($key)) $row[$key] = $val; //set by index (replace)
				else $row[] = $val; //add
			}
			foreach($row as $data) $this->tbody->open(new Element('td', $data));
		}
	}

	public function foot(array $values): void
	{
		//open foot
		if(!$this->tfoot)
		{
			$this->tfoot = new Element('tfoot');
			$this->open($this->tfoot);
			$this->close('tfoot');
		}
		else $this->tfoot->activate(); //close prev row (or any if is open)

		//add row
		$this->tfoot->open(new Element('tr', true));

		//add data
		if(array_is_list($values)) foreach($values as $value) $this->tfoot->open(new Element('td', $value));
		else
		{
			$row = [];
			foreach($values as $key => $val)
			{
				if (is_string($key)) //set by name
				{
					if(isset($this->columns[$key])) $row[$this->columns[$key]] = $val; //key exists (replace)
					elseif(count($this->columns) === 0) $row[] = $val; //add (only if map is empty)
				}
				elseif (is_int($key)) $row[$key] = $val; //set by index (replace)
				else $row[] = $val; //add
			}
			foreach($row as $data) $this->tfoot->open(new Element('td', $data));
		}
	}
	
	public function db(object $result): void
	{
		//head
		if(!$this->thead) //only if not exists
		{
			$fields = $result->fetch_fields();
			$this->head(array_column($fields, 'name'));
		}
		
		//body
		while($row = $result->fetch_assoc()) $this->body($row); //while($row = $result->fetch_object()) $this->body(get_object_vars($row));
	}
}
