<?php

declare(strict_types = 1);

class SVG extends Element
{
	public function __construct(mixed ...$attrib)
	{
		parent::__construct(strtolower(__CLASS__), false, $attrib);
	}

	//https://www.w3schools.com/graphics/svg_intro.asp

	//none of these functions are exceptional
	//therefore they do not need to be declared manually
	//the declaration is done automatically by Element::__call

	/*
	public function rect(mixed ...$data): object { return $this->add(new Element(__FUNCTION__, $data)); } //singular
	public function circle(mixed ...$data): object { return $this->add(new Element(__FUNCTION__, $data)); } //singular
	public function ellipse(mixed ...$data): object { return $this->add(new Element(__FUNCTION__, $data)); } //singular
	public function line(mixed ...$data): object { return $this->add(new Element(__FUNCTION__, $data)); } //singular
	public function polygon(mixed ...$data): object { return $this->add(new Element(__FUNCTION__, $data)); } //singular
	public function polyline(mixed ...$data): object { return $this->add(new Element(__FUNCTION__, $data)); } //singular
	public function image(mixed ...$data): object { return $this->add(new Element(__FUNCTION__, $data)); } //singular

	public function text(mixed ...$data): object { return $this->add(new Element(__FUNCTION__, $data)); } //define a text
	public function tspan(mixed ...$data): object { return $this->add(new Element(__FUNCTION__, $data)); } //mark up parts of a text (just like the HTML <span> element)
	public function textPath(mixed ...$data): object { return $this->add(new Element(__FUNCTION__, $data)); } //render a text along the shape of a path

	public function title(mixed ...$data): object { return $this->add(new Element(__FUNCTION__, $data)); } //not rendered short-text description (tooltip)
	public function desc(mixed ...$data): object { return $this->add(new Element(__FUNCTION__, $data)); } //not rendered description

	public function defs(mixed ...$data): object { return $this->add(new Element(__FUNCTION__, $data)); } //store graphical objects that will be used at a later time
	public function use(mixed ...$data): object { return $this->add(new Element(__FUNCTION__, $data)); } //clone element

	public function marker(mixed ...$data): object { return $this->add(new Element(__FUNCTION__, $data)); } //create a marker for the start, mid, and end of a <line>, <path>, <polyline> or <polygon>

	public function filter(mixed ...$data): object { return $this->add(new Element(__FUNCTION__, $data)); }
	public function feBlend(mixed ...$data): object { return $this->add(new Element(__FUNCTION__, $data)); }
	public function feColorMatrix(mixed ...$data): object { return $this->add(new Element(__FUNCTION__, $data)); }
	public function feComponentTransfer(mixed ...$data): object { return $this->add(new Element(__FUNCTION__, $data)); }
	public function feComposite(mixed ...$data): object { return $this->add(new Element(__FUNCTION__, $data)); }
	public function feConvolveMatrix(mixed ...$data): object { return $this->add(new Element(__FUNCTION__, $data)); }
	public function feDiffuseLighting(mixed ...$data): object { return $this->add(new Element(__FUNCTION__, $data)); }
	public function feDisplacementMap(mixed ...$data): object { return $this->add(new Element(__FUNCTION__, $data)); }
	public function feDistantLight(mixed ...$data): object { return $this->add(new Element(__FUNCTION__, $data)); }
	public function feDropShadow(mixed ...$data): object { return $this->add(new Element(__FUNCTION__, $data)); }
	public function feFlood(mixed ...$data): object { return $this->add(new Element(__FUNCTION__, $data)); }
	public function feFuncA(mixed ...$data): object { return $this->add(new Element(__FUNCTION__, $data)); }
	public function feFuncB(mixed ...$data): object { return $this->add(new Element(__FUNCTION__, $data)); }
	public function feFuncG(mixed ...$data): object { return $this->add(new Element(__FUNCTION__, $data)); }
	public function feFuncR(mixed ...$data): object { return $this->add(new Element(__FUNCTION__, $data)); }
	public function feGaussianBlur(mixed ...$data): object { return $this->add(new Element(__FUNCTION__, $data)); }
	public function feImage(mixed ...$data): object { return $this->add(new Element(__FUNCTION__, $data)); }
	public function feMerge(mixed ...$data): object { return $this->add(new Element(__FUNCTION__, $data)); }
	public function feMergeNode(mixed ...$data): object { return $this->add(new Element(__FUNCTION__, $data)); }
	public function feMorphology(mixed ...$data): object { return $this->add(new Element(__FUNCTION__, $data)); }
	public function feOffset(mixed ...$data): object { return $this->add(new Element(__FUNCTION__, $data)); }
	public function fePointLight(mixed ...$data): object { return $this->add(new Element(__FUNCTION__, $data)); }
	public function feSpecularLighting(mixed ...$data): object { return $this->add(new Element(__FUNCTION__, $data)); }
	public function feSpotLight(mixed ...$data): object { return $this->add(new Element(__FUNCTION__, $data)); }
	public function feTile(mixed ...$data): object { return $this->add(new Element(__FUNCTION__, $data)); }
	public function feTurbulence(mixed ...$data): object { return $this->add(new Element(__FUNCTION__, $data)); }

	public function linearGradient(mixed ...$data): object { return $this->add(new Element(__FUNCTION__, $data)); }
	public function radialGradient(mixed ...$data): object { return $this->add(new Element(__FUNCTION__, $data)); }
	public function stop(mixed ...$data): object { return $this->add(new Element(__FUNCTION__, $data)); } //singular (always a child of a <linearGradient> or <radialGradient> element)

	public function pattern(mixed ...$data): object { return $this->add(new Element(__FUNCTION__, $data)); }
	public function clipPath(mixed ...$data): object { return $this->add(new Element(__FUNCTION__, $data)); }
	public function mask(mixed ...$data): object { return $this->add(new Element(__FUNCTION__, $data)); }

	//animation
	public function set(mixed ...$data): object { return $this->add(new Element(__FUNCTION__, $data)); } //singular
	public function animate(mixed ...$data): object { return $this->add(new Element(__FUNCTION__, $data)); } //singular
	public function animateMotion(mixed ...$data): object { return $this->add(new Element(__FUNCTION__, $data)); } //singular
	public function animateTransform(mixed ...$data): object { return $this->add(new Element(__FUNCTION__, $data)); } //singular
	*/
}
