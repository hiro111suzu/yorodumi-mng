<?php
require_once( "wikipe-common.php" );

define( 'HEAD_TERM', 'INSERT INTO `redirect` VALUES (' );
define( 'NG_CATEG', array_filter( preg_split( "/\n|\r/", "
UK Parliament constituency
constituency
lunar crater
Eastern Orthodox liturgics
constituency
Toronto
rocket
Wehrmacht
Manhattan
Ottawa
MBTA station
Stargate
Pokémon
New York City Subway
soccer
Taiwan
New York City Subway service
provincial electoral district
Switzerland
cricketer
judge
Denmark
Michigan
given name
Cyrillic
MBTA
county
Star Trek: Enterprise
TV
Seattle
Dáil Éireann constituency
radio
athlete
soundtrack
Ohio
economics
football
American band
Marxist–Leninist
Colorado
Peru
soldier
Poland
architecture
Finland
MBTA bus
solitaire
game show
Boston
NY
planet
broadcaster
tennis
brand
Singapore
Dungeons & Dragons
London
Virginia
New York City Subway car
Star Wars
USA
New Zealand politician
Scotland
playwright
diplomat
Manitoba riding
Berlin U-Bahn
record label
weapon
Harry Potter
priest
lawyer
U.S.
Pennsylvania
golf
Massachusetts
Texas
airline
Florida
World of Darkness
naval officer
publisher
Maryland
New Jersey
Latter Day Saints
computer game
economist
Massachusetts Bay Transportation Authority
Chicago
mayor
American politician
Royal Navy officer
telecommunications
producer
China
drummer
Transformers
dinghy
beer
U.S. TV series
Minnesota
Stargate SG-1
Paris
7-Flushing
Union Army
computer scientist
Ilia
Magic: The Gathering
Stargate-SG-1
Ontario politician
golfer
Tennessee
Portugal
Sesame Street
guitarist
Alberta
Amtrak station
StarCraft
Romania
religion
FM
Irish politician
sculptor
Russia
Manitoba politician
Los Angeles
Montreal Metro
Mortal Kombat
municipality
Hasidic dynasty
Street Fighter
miniseries
U.S. state
cocktail
Italy
MTR
clothing
Alaska
anime
Belgium
Athens
Washington, D.C.
Pittsburgh
activist
New York City
video game series
Power Rangers
Warhammer
Star Trek: The Original Series
photographer
Disney
currency
Georgia
Argentina
The Matrix
astronaut
gaming
radio station
Israel
tank
Cambridge
Malaysia
political party
Berlin
British Columbia
New Hampshire
tribe
chess player
retailer
Pokemon
Haydn
British band
game designer
audio drama
art
sailing
Brazil
Antarctica
Beethoven
village
city
district
politician
United States
Paris Metro
moon
Canada
United Kingdom
UK
river
movie
author
company
Middle-earth
province
composer
state
region
Alias episode
British politician
ice hockey
electoral district
Australia
New Zealand
automobile
card game
department
Babylon 5
series
artist
bishop
California
governor
borough
dance
crater
snooker
France
politics
spacecraft
boxer
Mozart
chess
Michigan highway
basketball
professional wrestling
Ontario
car
opera
journalist
missile
England
Germany
footballer
short story
Oregon
Sweden
ship
cricket
manga
comedian
Hong Kong
Canadian politician
Star Trek: The Next Generation
astronomer
IRT Lexington Avenue Line
philosopher
painter
rapper
newspaper
The Simpsons
satellite
DC Comics
The Twilight Zone
businessman
board game
Shostakovich
novelist
Washington
architect
Buffy the Vampire Slayer
US
India
rugby union
Doctor Who
New York
franchise
Norway
wrestler
train
director
theologian
island
Spain
role-playing game
poem
operating system
historian
snooker player
South Africa
British Army officer
comic strip
Netherlands
cartoonist
sport
explorer
Discworld
New York Subway
Australian politician
Dragon Ball
astronomy
engineer
New South Wales
IRT Pelham Line
comics
poker
magazine
musician
constellation
vehicle
sex
play
television
sexuality
emulator
mythology
deity
character
Star Trek
Star Trek: Voyager
game
musical
surname
god
decade
 The Simpsons
Disney Comics
Nintendo Character
goddess
programming language
role-playing game system
country
R11
writer
watercraft
Ireland
America
American football
Christian
Homer
Iran
Japan
Marvel Comics
Space Odyssey
TV channel
TV series
Unix shell
actor
actress
album
animation
announcer
band
band
baseball
book
comic
family law
film
language
law
military
music
novel
pen name
poet
robot
singer
song
town
video game
U.S. Army
finance
aircraft
gridiron football
UK TV series
rugby league
horse
Vidhan Sabha constituency
year
airport
Great Britain
MP
Missouri
number
cyclist
TX
swimmer
Australian footballer
season 1
wine
CDP
season 2
Illinois
Arkansas
women
international airport
Wisconsin
Martian crater
racing driver
Lok Sabha constituency
season 3
rower
Greece
Sri Lanka
Soviet Union
Connecticut
season 4
Indiana
Croatia
minister
field hockey
painting
volleyball
SEPTA station
community development block
Scottish footballer
Dungeons and Dragons
" ) ) );


//. prep db

_line( 'wikipe EN titles' );
$fh_xml = gzopen( FN_EN_STUB_XML, 'r' );

$sqlite = new cls_sqlw([
	'fn' => FN_DB_EN_TITLE, 
	'cols' => [
		'title UNIQUE' ,
		'rd_to'
	],
	'indexcols' => [ 'title' ],
	'new' => true
]);

$sqlite_nc = new cls_sqlw([
	'fn' => FN_DB_EN_TITLE_NC, 
	'cols' => [
		'title UNIQUE COLLATE NOCASE' ,
		'rd_to'
	],
	'indexcols' => [ 'title' ],
	'new' => true
]);

//.. main
$xml_ar = [];
$cat_count = [];
while ( true ) {
	$line = $line = fgets( $fh_xml, 50000 );
	if ( $line === false ) {
		break;
	}
	$line = trim( $line );
	//.. XMLのかたまり
	if ( $line == '<page>' ) {
		$xml_ar = [];
		$flg_ns0 = false;
	}
	// ns0?
	if ( $line == '<ns>0</ns>' )
		$flg_ns0 = true;
	$xml_ar[] = $line;

	if ( $line != '</page>' ) continue;
	_cnt( 'total' );
	if ( ! $flg_ns0 ) continue;
	
	//.. XML解釈
	if ( _count( 500000, 0 ) ) break;
	$xml = simplexml_load_string( implode( '', $xml_ar ) );
	$rdct = (string)$xml->redirect['title'];
	$title = (string)$xml->title;
	foreach ( NG_CATEG as $n ) {
		if (
			_instr( " ($n)", $title ) ||
			_instr( " ($n)", $rdct ) 
		) {
			_cnt( 'NG categ' );
			$title = '';
			break;
		}
	}
	if ( ! $title ) continue;
	
	//.. カテゴリあったら、保存
	if ( _instr( '(', $title ) ) {
		$c = _reg_rep( $title, [ '/^.+? \(/' => '', '/\).*$/' => '' ] );
		if ( strlen( _numonly( $c ) ) < 4 ) {
			++ $cat_count[ $c ];
		}
	}

	//.. DB保存
	$sqlite->set([ $title, $rdct ?: '@' ]);

	//- caseなし
//	if ( strtolower( $title ) == strtolower( $rdct ) )
//		$rdct = '@';
	$sqlite_nc->set([ $title, $rdct ?: '@' ]);

	_cnt( 'doc' );
	$xml_ar = [];
	$flg_ns0 = false;
}
gzclose( $fh_xml );

//. 終了処理
foreach ( $cat_count as $k => $v )  {
	if ( $v < 1 )
		unset( $cat_count[ $k ] );
}
arsort( $cat_count );
_tsv_save( DN_WIKIPE. '/categ_count.tsv', $cat_count );

_cnt();
$sqlite->end();
$sqlite_nc->end();


