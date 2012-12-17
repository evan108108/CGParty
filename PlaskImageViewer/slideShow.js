//
// A Plask Slideshow.
//

var plask = require('plask');
var net = require('net');

var display = {
	width: 2560,
	height: 1440
};

plask.simpleWindow({
  settings: { //Basic app window settings
    width: display.width, //Window width
    height: display.height, //Window height
		fullscreen: true, //Remove all window chrome
		display: 4, //Target display. For a second display use `display: 2`
  },

  init: function() {
		var media_path = '/Users/evan.frolich/Sites/react/CGParty/media/';
		this.bg_img = plask.SkCanvas.createFromImage('/Users/evan.frolich/Sites/react/CGParty/assets/images/floor.png');
		this.logo_img = plask.SkCanvas.createFromImage('/Users/evan.frolich/Sites/react/CGParty/assets/images/CG_Circle.png');
		var canvas = this.canvas, paint = this.paint, me = this;
    this.image = null;
    this.image_pos = {x: 0, y: 0};
		
		//Open a connection to our local Socket Server on Port 4000
		var stream = net.createConnection(4000);	
		
		//Add a listener for socket connect and log it.
		stream.addListener("connect", function(){
		 console.log('connected');
		});

		//Add a listener for data transmitted over the socket
		stream.addListener("data", function(data){
			console.log(media_path + JSON.parse(data).name + "\n");
			//Try to load image and draw it to the canvas
			try { 
				me.image = plask.SkCanvas.createFromImage(media_path + JSON.parse(data).name);
				me.clearCanvas();	
				me.redraw(); //Redraw the stage with the new image
			} catch(e) { //Catch error incase the image fails to load
				console.log(e);	
			}
		});
		
		this.clearCanvas();
		this.drawLogo();
  },
	draw: function() {
    var canvas = this.canvas, paint = this.paint;
    if (this.image !== null) {
      var p = this.scaleImage(this.image.width, this.image.height);
			console.log(p.TL.x, p.TL.y, p.BR.x, p.BR.y);
      canvas.drawCanvas(
				paint, this.image,
        p.TL.x, p.TL.y, p.BR.x, p.BR.y,
        0, 0, this.image.width, this.image.height
			);
    }
  },
	clearCanvas: function(){ //Clears all images off the stage by re-drawing the background image 
		if(this.bg_img !== null) {
			this.canvas.drawCanvas(
				this.paint, this.bg_img,
        0, 0, display.width, display.height,
        0, 0, this.bg_img.width, this.bg_img.height
			);
		}
		else canvas.clear(230, 230, 230, 255); //If no bg_img exists lets just paint a solid grey to the canvas
	},
	drawLogo: function(){ //Draw the logo image to the center of the stage
		if(this.logo_img !== null) {
			var XOffset = (display.width - this.logo_img.width) / 2;
			var YOffset = (display.height - this.logo_img.height) / 2;
			this.canvas.drawCanvas(
				this.paint, this.logo_img,
        XOffset, YOffset, XOffset + this.logo_img.width, YOffset + this.logo_img.height,
        0, 0, this.logo_img.width, this.logo_img.height
			);
		}
	},
	scaleImage: function(imgWidth, imgHeight) {
		var winWidth = display.width, winHeight = display.height;
		var ImgAspect = imgWidth / imgHeight
		
		//So then if the image is wider rather than taller, set the width and figure out the height
		if ((imgWidth/imgHeight) > (winWidth/winHeight)) {
			NewImgWidth = winWidth;
			NewImgHeight = (winWidth * imgHeight)/ imgWidth;
		}
		//And if the image is taller rather than wider, then set the height and figure out the width
		else if ((imgWidth/imgHeight) < (winWidth/winHeight)) {
			NewImgWidth = (winHeight * imgWidth)/ imgHeight;
			NewImgHeight = winHeight;
		}
		//And because it is entirely possible that the image could be the exact same size/aspect ratio of the desired area, so we have that covered as well
		else if ((imgWidth/imgHeight) == (winWidth/winHeight)) {
			NewImgWidth = winWidth;
			NewImgHeight = winHeight;
		}

		//X Y offsets
		var XOffset = (winWidth - NewImgWidth) / 2;
		var YOffset = (winHeight - NewImgHeight) / 2;

		return { 
			TL: { x: XOffset, y: YOffset }, //Top left cords
			BR: {	x: (NewImgWidth + XOffset), y: (NewImgHeight + YOffset) } //Bottom right cords
		};
	}
});


