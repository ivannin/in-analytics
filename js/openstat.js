/** 
 *	Openstat parser v3
 *
 *  В этой версии изменено
 *  1. Проверка и включение парсера только при наличии _openstat метки и отсуствия UTM
 *	2. Место размещение пишется в объявление, а не в компанию
 */
var  OpenStatParser  =   {        
    _params:  {},
    _parsed:  false,
	_decode64:   function(data)  {            
        if  (typeof  window['atob']  ===  'function')  {                
            return  atob(data);            
        }            
        var  b64  =  "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";            
        var  o1,  o2,  o3,  h1,  h2,  h3,  h4,  bits,  i  =  0,
                                ac  =  0,
                                dec  =  '',
                                tmp_arr  =   [];            
        if  (!data)  {                
            return  data;            
        }            
        data  +=  '';            
        do  {                
            h1  =  b64.indexOf(data.charAt(i++));                
            h2  =  b64.indexOf(data.charAt(i++));                
            h3  =  b64.indexOf(data.charAt(i++));                
            h4  =  b64.indexOf(data.charAt(i++));                
            bits  =  h1  <<  18  |  h2  <<  12  |  h3  <<  6  |  h4;                
            o1  =  bits  >>  16  &  0xff;                
            o2  =  bits  >>  8  &  0xff;                
            o3  =  bits  &  0xff;                
            if  (h3  ==  64)  {                    
                tmp_arr[ac++]  =  String.fromCharCode(o1);                
            } 
            else  if  (h4  ==  64)  {                    
                tmp_arr[ac++]  =  String.fromCharCode(o1,  o2);                
            } 
            else  {                    
                tmp_arr[ac++]  =  String.fromCharCode(o1,  o2,  o3);                
            }            
        }  while   (i  <  data.length);            
        dec  =  tmp_arr.join('');            
        return  dec;        
    },
            
	_parse:   function()  {            
        var  prmstr  =  window.location.search.substr(1);            
        var  prmarr  =  prmstr.split('&');            
        this._params  =   {};            
        for  (var  i  =  0;  i  <  prmarr.length;  i++)  {                
            var  tmparr  =  prmarr[i].split('=');                
            this._params[tmparr[0]]  =  tmparr[1];            
        }
             
        this._parsed  =  true;        
    },
            
	hasMarker:   function()  {
		// Проверка на UTM
		if (window.location.search.indexOf('utm_') > 0) return false;
		
		// Парсим параметры         
        if  (!this._parsed)  {                
            this._parse();            
        }
		// Есть ли _openstat ?         
        return  (typeof  this._params['_openstat']  !==  'undefined')  ?  true  :  false;        
    },
            
	buildCampaignParams:   function()  {            
        if  (!this.hasMarker())  {                
            return  false;            
        }            
        var  openstat  =  this._decode64(this._params['_openstat']);            
        var  statarr  =  openstat.split(';');            
        return {
            'campaignName': statarr[1],
            'campaignSource': statarr[0],
            'campaignMedium': 'cpc',
            'campaignContent': statarr[2] + ' (' + statarr[3] + ')'
        };      
    }    
} 