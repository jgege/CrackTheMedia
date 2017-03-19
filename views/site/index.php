<style>
    body{ margin: 0;
        background-color: #212121;
    }
    .container { background-color: #212121;
    }
    .header { position: relative;
        height: 128px;
        background-color: #F5AB35;
    }
    .title_image { float: left;
    }
    .title_text { float: left;
    }
    .title_text h1 { font-size: 52px;
    }
    .content { margin-top: 10px;
        padding: 64px;
    }
    .content h1 { color: white;
    }
    .code { display: inline-block;
        margin: 16px;
        padding: 16px;
        border-style: none;
        border-radius: 8px;
        background-color: darkgray;
        width: auto;
    }
</style>
<div class="header">
    <div class="title_image">
        <img src="extension-icon.png" alt="title image"/>
    </div>
   <div class="title_text">
       <h1>Crack the Media API</h1>
   </div>
</div>
<div class="content">
    <h1>Media outlet basic information</h1>
    <div class="code">
        GET http://crackthemedia.ml/boyan/api/index?query={URL_QUERY_HERE}
    </div>
    <h1>Image original source information</h1>
    <div class="code">
        GET http://crackthemedia.ml/boyan/api/image?query={URL_IMG_SRC_HERE}
    </div>
    <h1>Check article against other sources</h1>
    <div class="code">
        GET http://crackthemedia.ml/boyan/api/similarity?query={ARTICLE_NAME_HERE}
    </div>
</div>