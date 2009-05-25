<?php

Krai::Uses(
	//Krai::$INCLUDES."/lib/wiki_parser/Text/Wiki.php",
	//Krai::$INCLUDES."/lib/markdown.class.php",
	Krai::$INCLUDES."/lib/textile.class.php"
);

abstract class Markup
{

	public static $WikiParser = null;
	public static $Textile = null;
	public static $Markdown = null;

	/*protected static function InitWikiParser()
	{
		self::$WikiParser =& Text_Wiki::singleton('Default',SETTINGS::$TextWikiRules); //call ->transform($text,'Xhtml');
	}*/

	protected static function InitTextile()
	{
		self::$Textile = new Textile(); //call ->TextileRestricted($text)
	}

	/*protected static function InitMarkdown()
	{
		$parser_class = MARKDOWN_PARSER_CLASS;
		self::$Markdown = new $parser_class(); //call ->transform($text)
	}*/

	public static function DoMarkup($text, $type)
	{
		switch($type)
		{
			/*case "text_wiki":
				if(is_null(self::$WikiParser))
				{
					self::InitWikiParser();
				}
				return self::$WikiParser->transform($text, 'Xhtml');
				break;*/
			case "textile":
				if(is_null(self::$Textile))
				{
					self::InitTextile();
				}
				return self::$Textile->TextileThis(self::DeQuote($text),'','','','','external');
				break;
			/*case "markdown":
				if(is_null(self::$Markdown))
				{
					self::InitMarkdown();
				}
				return self::$Markdown->transform(self::DeQuote($text));
				break;*/
			default:
				return "";
		}
	}

	public static function DoMarkupRss($text, $type)
	{
		switch($type)
		{
			/*case "text_wiki":
				if(is_null(self::$WikiParser))
				{
					self::InitWikiParser();
				}
				return self::$WikiParser->transform($text, 'Rss');
				break;*/
			default:
				return self::DoMarkup($text, $type);
		}
	}

	public static function GetMarkupValue($markup)
	{
		if(!in_array($markup, array("textile"/*,"markdown","text_wiki"*/)))
		{
			return "textile";
		}
		else
		{
			return $markup;
		}
	}

	protected static function DeQuote($text)
	{
		return preg_replace(array("!&quot;!","!&#39;!","!&#092;!"),array("\"","'",'\\'), $text);
	}

}
