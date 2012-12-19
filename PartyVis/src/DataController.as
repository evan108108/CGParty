package
{
	import flash.events.Event;
	import flash.events.EventDispatcher;
	import flash.events.IEventDispatcher;
	import flash.events.IOErrorEvent;
	import flash.events.ProgressEvent;
	import flash.events.TimerEvent;
	import flash.net.Socket;
	import flash.utils.Timer;
	
	import mx.collections.ArrayCollection;
	
	public class DataController extends EventDispatcher
	{
		private var socket:Socket;
		public var incomingDataBuff:ArrayCollection = new ArrayCollection();
		public var backupDataBuff:ArrayCollection = new ArrayCollection();
		public var timeoutTimer:Timer = new Timer(10000);
		private var sUrl:String = "";
		private var sPort:int = 0;
		
		public function DataController(target:IEventDispatcher=null)
		{
			trace('[DataController:DataController]');
			//TODO: implement function
			super(target);
			
			timeoutTimer.addEventListener(TimerEvent.TIMER, doReconnect, false, 0, true);
		}
		
		public function doReconnect(event:TimerEvent):void
		{
			trace('[DataController:doReconnect]');
			timeoutTimer.stop();
			this.connect(sUrl, sPort);
		}
		
		public function connect(url:String, port:int):void
		{
			trace('[DataController:connect] '+url+':'+port);
			sUrl = url;
			sPort = port;
			socket = new Socket(url, port);
			socket.addEventListener(Event.CONNECT, onConnect, false, 0, true);
			socket.addEventListener(ProgressEvent.SOCKET_DATA, onSocketData, false, 0, true);
			socket.addEventListener(IOErrorEvent.IO_ERROR, onIOError, false, 0, true);
			socket.addEventListener(Event.CLOSE, onClose, false, 0, true);
		}
		
		private function onConnect(event:Event):void
		{
			trace('[DataController:onConnect]');
		}
		
		public function disconnect():void
		{
			trace('[DataController:disconnect]');
			socket.close();
		}
		
		private function onClose(event:Event):void {
			trace('[DataController:onClose]');
			// set the incoming 
			incomingDataBuff = backupDataBuff;
			timeoutTimer.start();
		}
		
		// socket event handlers
		private function onIOError(event:IOErrorEvent):void
		{
			trace('[DataController:onIOError] '+event.text);
			incomingDataBuff = backupDataBuff;
			timeoutTimer.start();
			
		}
		
		private function onSocketData(event:ProgressEvent):void
		{
			//trace('[DataController:onSocketData] '+event.type);
			
			if(socket.bytesAvailable == 0) return;
			var data_str:String = socket.readUTFBytes(socket.bytesAvailable);
			//trace('data: '+data_str);
		
			var data_json:Object = JSON.parse(data_str);
			
			switch(data_json.type) {
				case 'file':
					//trace('[DataController:onSocketData] filename: '+data_json.name);
					// add to queue
					data_json.textFile = "none";
					incomingDataBuff.addItem(data_json);
					//trace('[DataController:onSocketData] queueLength: '+incomingDataBuff.length);
	
					// put on display
					break;
				case 'dir':
					trace('[DataController:onSocketData] folder: '+data_json.name);
					// break them out and add them to the queue
					var obj:Object;
					for(var i:int=0; i<data_json.contents.length; i++) {
						obj = new Object();
						obj.name = data_json.name+'/'+data_json.contents[i];
						obj.text = data_json.text;//"file:/Users/controlgroup/sites/CGParty/CG Party Visuals 2012/Contents/Resources/media/"+data_json.name;
						incomingDataBuff.addItem(obj);
					}
					//obj = new Object();
					//obj.name = "assets/images/CG_Circle.png";
					//incomingDataBuff.addItem(obj);
					break;
				default:
					break;
			}
		}
	}
}