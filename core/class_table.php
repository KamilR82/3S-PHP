<?php

declare(strict_types = 1);

class Table
{
	const mark_up = '&#9650;'; //&uarr;
	const mark_down = '&#9660;'; //&darr;

	private array $table_attrib = [];

	private ?string $caption = null;

	private ?string $sort = null;

	private int $columns = 0;

	private array $colgroup = []; //[[span, class], [span, class]]
	private array $head = []; //[[r0c0, r0c1], [r1c0, r1c1]]
	private array $body = []; //[[r0c0, r0c1], [r1c0, r1c1], [r2c0, r2c1]]
	private array $foot = []; //[[r0c0, r0c1]]

	private function Row(array $values): string
	{
		$output = HTML::Tag('tr', true);
		if($this->columns)
		{
			for ($i = 0; $i < $this->columns; $i++) $output .= HTML::Tag('td', $values[$i] ?? '');
		}
		else //columns counter not set
		{
			foreach($values as $value) $output .= HTML::Tag('td', $value);
		}
		$output .= HTML::Tag('tr', false);
		return $output;
	}

	private function RowHead(array $values): string
	{

		$output = HTML::Tag('tr', true);
		if($this->columns)
		{
			for ($i = 0; $i < $this->columns; $i++) $output .= $this->HeadTh($values[$i] ?? '');
		}
		else //columns counter not set
		{
			foreach($values as $value) $output .= HTML::Tag($value);
		}
		$output .= HTML::Tag('tr', false);
		return $output;
	}

	private function HeadTh(string|array $value): string
	{
		$label = '';
		$column = '';
		$additional = '';

		if(is_array($value)) //sortable caption
		{
			if(array_is_list($value))
			{
				switch(count($value))
				{
					case 4: //label, column, title, additional
						$additional = rtrim(' '.$value[3]);
					case 3: //label, column, title
						$additional = ' title="'.$value[2].'"'; //title
					case 2: //label, column
						$column = $value[1];
					case 1: //only label (column = label)
						$label = $value[0];
						if(Str::IsEmpty($column)) $column = $label;
				}
			}
			else
			{
				$additional = rtrim(' '.$value['additional'] ?? $value['a'] ?? ''); //additional
				if(isset($value['title'])) $additional = ' title="'.$value['title'].'"'; //title
				else if(isset($value['t'])) $additional = ' title="'.$value['t'].'"'; //title
				$column = $value['column'] ?? $value['c'] ?? ''; //column
				$label = $value['label'] ?? $value['l'] ?? $value[0] ?? ''; //label
			}
		}
		else $label = $value; //only string

		//output
		$output = '<th'.$additional.'>';
		if(Str::NotEmpty($column))
		{
			if(Str::NotEmpty($this->sort))
			{
				if(strcasecmp($this->sort, $column) == 0) //active sort on this column?
				{
					if(Str::IsCapitalLetter($this->sort))
					{
						$output .= self::mark_down.' ';
						$column = lcfirst($column);
					}
					else
					{
						$output .= self::mark_up.' ';
						$column = ucfirst($column);
					}
				}
			}
			$output .= href($label, Request::Modify(['sort' => $column]));
		}
		else $output .= $label;
		$output .= '</th>';
		return $output;
	}

//public
	public function __construct(?string $caption = null, ?string $class = null, ?string $id = null)
	{
		$this->caption = $caption;
		$this->table_attrib = array_merge($this->table_attrib, compact('class', 'id'));
	}

	public function Caption(?string $caption = null): void
	{
		$this->caption = $caption;
	}

	public function ColGroup(?int $span = null, ?string $class = null): void
	{
		array_push($this->colgroup, ['span' => $span, 'class' => $class]);
	}

	public function Head(array $values = []): void //add head row
	{
		$this->columns = max($this->columns, count($values)); //set columns counter
		array_push($this->head, $values);
	}

	public function Body(array $values = []): void //add body row
	{
		array_push($this->body, $values);
	}

	public function Foot(array $values = []): void //add foot row
	{
		array_push($this->foot, $values);
	}

	public function Clear(): void
	{
		$this->columns = 0;
		$this->colgroup = [];
		$this->head = [];
		$this->body = [];
		$this->foot = [];
	}

	public function Data(array $values = []): void
	{
		$this->Clear();
		if(is_array($values[0] ?? null))
		{
			if(!array_is_list($values[0])) $this->Head(array_keys($values[0])); //set keys as header cells
			else $this->columns = count($values[0]); //set only column count
			$this->body = $values; //replace body content
		}
	}

	public function echo(): void
	{
		//table elements must be used in the following context:
		//<table><caption> <colgroup><col> <thead><tr><th> <tbody><tr><td> <tfoot><tr><td>
		echo HTML::Tag('table', true, $this->table_attrib);
		//caption
		if(Str::NotEmpty($this->caption)) echo HTML::Tag('caption', $this->caption);
		//colgroup - specifies a group of one or more columns in a table for formatting
		if(count($this->colgroup))
		{
			echo HTML::Tag('colgroup', true);
			foreach($this->colgroup as $col) echo HTML::Tag('col', null, $col);
			echo HTML::Tag('colgroup', false);
		}
		//head
		if(count($this->head))
		{
			echo HTML::Tag('thead', true);
			foreach($this->head as $row) echo $this->RowHead($row);
			echo HTML::Tag('thead', false);
		}
		//body
		if(count($this->body))
		{
			echo HTML::Tag('tbody', true);
			foreach($this->body as $row) echo $this->Row($row);
			echo HTML::Tag('tbody', false);
		}
		//foot
		if(count($this->foot))
		{
			echo HTML::Tag('tfoot', true);
			foreach($this->foot as $row) echo $this->Row($row);
			echo HTML::Tag('tfoot', false);
		}
		//close
		echo HTML::Tag('table', false);
	}
}
