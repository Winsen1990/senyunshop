<?php
abstract class WechatResponse
{
    protected $fromUserName;
    protected $toUserName;
    protected $template;

    public function __construct($fromUserName, $toUserName)
    {
        $this->fromUserName = $fromUserName;
        $this->toUserName = $toUserName;
    }

    abstract public function __toString();
}

class TextResponse extends WechatResponse
{
    protected $content;

    public function __construct($fromUserName, $toUserName, $content)
    {
        parent::__construct($fromUserName, $toUserName);
        $this->content = $content;
        $this->template =<<<XML
<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%d</CreateTime>
<MsgType><![CDATA[text]]></MsgType>
<Content><![CDATA[%s]]></Content>
</xml>
XML;
    }

    public function setContent($content)
    {
        $this->content = $content;
    }

    public function __toString()
    {
        return sprintf($this->template, $this->toUserName, $this->fromUserName, time(), $this->content);
    }
}

class ImageResponse extends WechatResponse
{
    protected $mediaId;

    public function __construct($fromUserName, $toUserName, $mediaId)
    {
        parent::__construct($fromUserName, $toUserName);
        $this->mediaId = $mediaId;
        $this->template =<<<XML
<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%d</CreateTime>
<MsgType><![CDATA[image]]></MsgType>
<Image>
<MediaId><![CDATA[%d]]></MediaId>
</Image>
</xml>
XML;
    }

    public function __toString()
    {
        return sprintf($this->template, $this->toUserName, $this->fromUserName, time(), $this->mediaId);
    }
}

class VoiceResponse extends WechatResponse
{
    protected $mediaId;

    public function __construct($fromUserName, $toUserName, $mediaId)
    {
        parent::__construct($fromUserName, $toUserName);
        $this->mediaId = $mediaId;
        $this->template = <<<XML
<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%d</CreateTime>
<MsgType><![CDATA[voice]]></MsgType>
<Voice>
<MediaId><![CDATA[%d]]></MediaId>
</Voice>
</xml>
XML;
    }

    public function __toString()
    {
        return sprintf($this->template, $this->toUserName, $this->fromUserName, time(), $this->mediaId);
    }
}

class VideoResponse extends WechatResponse
{
    protected $mediaId;
    protected $title;
    protected $description;

    public function __construct($fromUserName, $toUserName, $mediaId, $title, $description)
    {
        parent::__construct($fromUserName, $toUserName);
        $this->mediaId = $mediaId;
        $this->title = $title;
        $this->description = $this->description;
        $this->template = <<<XML
<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%d</CreateTime>
<MsgType><![CDATA[video]]></MsgType>
<Video>
<MediaId><![CDATA[%d]]></MediaId>
<Title><![CDATA[%s]]></Title>
<Description><![CDATA[%s]]></Description>
</Video> 
</xml>
XML;
    }

    public function __toString()
    {
        return sprintf($this->template, $this->toUserName, $this->fromUserName, time(), 
                        $this->mediaId, $this->title, $this->description);
    }
}

class MusicResponse extends WechatResponse
{
    protected $title;
    protected $description;
    protected $musicUrl;
    protected $HQMusicUrl;
    protected $thumbMediaId;

    public function __construct($fromUserName, $toUserName, $title, $description, $musicUrl, $HQMusicUrl, $thumbMediaId)
    {
        parent::__construct($fromUserName, $toUserName);
        $this->title = $title;
        $this->description = $description;
        $this->musicUrl = $musicurl;
        $this->HQMusicUrl = $HQMusicUrl;
        $this->thumbMediaId = $thumbMediaId;

        $this->template = <<<XML
<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%d</CreateTime>
<MsgType><![CDATA[music]]></MsgType>
<Music>
<Title><![CDATA[%s]]></Title>
<Description><![CDATA[%s]]></Description>
<MusicUrl><![CDATA[%s]]></MusicUrl>
<HQMusicUrl><![CDATA[%s]]></HQMusicUrl>
<ThumbMediaId><![CDATA[%d]]></ThumbMediaId>
</Music>
</xml>
XML;
    }

    public function __toString()
    {
        return sprintf($this->template, $this->toUserName, $this->fromUserName, time(), 
                       $this->title, $this->description, $this->musicUrl, $this->HQMusicUrl, $this->thumbMediaId);
    }
}

class NewsResponse extends WechatResponse
{
    protected $items;
    protected $itemTemplate;
    protected $append;

    public function __construct($fromUserName, $toUserName, $items, $append = true)
    {
        parent::__construct($fromUserName, $toUserName);
        $this->items = $items;
        $this->append = $append;
        $this->template = <<<XML
<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%d</CreateTime>
<MsgType><![CDATA[news]]></MsgType>
<ArticleCount>%d</ArticleCount>
<Articles>
%s
</Articles>
</xml>
XML;
        $this->itemTemplate = <<<XML
<item>
<Title><![CDATA[%s]]></Title> 
<Description><![CDATA[%s]]></Description>
<PicUrl><![CDATA[%s]]></PicUrl>
<Url><![CDATA[%s]]></Url>
</item>
XML;
    }

    public function __toString()
    {
        $count = count($this->items);

        $content = '';
        foreach($this->items as $item)
        {
            if($this->append)
            {
                $item['url'] = $item['url'].'?uid='.base64_encode($this->toUserName);
            } else {
                $item['url'] = $item['url'];
            }
            $content .= sprintf($this->itemTemplate, $item['title'], $item['description'], $item['picUrl'], $item['url']);
        }

        return sprintf($this->template, $this->toUserName, $this->fromUserName, time(),
                       $count, $content);
    }
}

class MultiServerTransfer extends WechatResponse
{
    protected $kfAccount;

    public function __construct($fromUserName, $toUserName, $kfAccount)
    {
        parent::__construct($fromUserName, $toUserName);
        $this->kfAccount = $kfAccount;

        $this->template =<<<XML
<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%d</CreateTime>
<MsgType><![CDATA[transfer_customer_service]]></MsgType>
<TransInfo>
	<KfAccount>%s</KfAccount>
</TransInfo>
</xml>
XML;
    }

    public function __toString()
    {
        return sprintf($this->template, $this->toUserName, $this->fromUserName, time(), $this->kfAccount);
    }
}
