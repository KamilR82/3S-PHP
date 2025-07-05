<?php declare(strict_types = 1);

define('CONFIG_FILE', './../config.php'); //load config file (supported types .ini .env .php)
require_once($_SERVER['DOCUMENT_ROOT'].'/core/singleton.php'); //initialize framework

Page::Start('SVG Example'); //set page title

//heading
h1(Page::Title()); //get page title

h3('Circle');
$svg = Page::SVG(width: 100, height: 100);
$svg->circle(cx: 50, cy: 50, r: 40, fill: 'none', stroke: 'black');

$svg = Page::SVG(width: 100, height: 100);
//$svg->circle(cx: 50, cy: 50, r: 40, fill: 'blue', stroke: 'red', stroke-width: 4); //wrong - key can't contain hyphen
$svg->circle(['stroke-width' => 4], cx: 50, cy: 50, r: 40, fill: 'blue', stroke: 'red'); //ok - this is correct

h3('Shapes');
$svg = Page::SVG(width: 600, height: 220);
$svg->polygon(points: '50,10 0,190 100,190', fill: 'lime');
$svg->rect(width: 150, height: 100, x: 120, y: 50, fill: 'blue');
$svg->circle(cx: 350, cy: 100, r: 45, fill: 'red');
$svg->text('I love SVG!', x: 420, y: 100, fill: 'red');

h3('Text');
$svg = Page::SVG(width: 350, height: 200);
$svg->path(id: 'lineAC', d: 'M 30 180 q 150 -250 300 0', stroke: 'blue', fill: 'none');
$svg->text(style: 'fill:red; font-size:25px;');
$svg->textPath('I love SVG! I love SVG!', href: '#lineAC', startOffset: 80);

h3('Polygon');
$svg = Page::SVG(width: 500, height: 210);
$svg->polygon(points: '100,10 40,198 190,78 10,78 160,198', fill: 'lime', style: 'stroke:purple;stroke-width:5;');

h3('Ellipses');
$svg = Page::SVG(width: 500, height: 210);
$svg->ellipse(cx: 240, cy: 100, rx: 220, ry: 30, fill: 'purple', stroke: 'black');
$svg->ellipse(cx: 220, cy: 70, rx: 190, ry: 20, fill: 'lime', stroke: 'black');
$svg->ellipse(cx: 210, cy: 45, rx: 170, ry: 15, fill: 'yellow', stroke: 'black');

h3('Link');
$svg = Page::SVG(width: 200, height: 30);
$svg->a(href: 'index.php');
$svg->text('I love SVG!', x: 5, y: 15, fill: 'red');

h3('Logo');
$svg = Page::SVG(width: 100, height: 100);
$svg->defs(true);
$svg->linearGradient(id: 'grad1', gradientTransform: 'rotate(45)');
$svg->stop(['offset'=>'0%', 'stop-color'=>'yellow']);
$svg->stop(['offset'=>'100%', 'stop-color'=>'red']);
$svg->defs(false);
$svg->circle(cx: 50, cy: 50, r: 50, fill: 'url(#grad1)');
$svg->text('3S', ['font-size'=>'64', 'font-family'=>'Verdana'], x: 8, y: 74, fill: '#ffffff');
