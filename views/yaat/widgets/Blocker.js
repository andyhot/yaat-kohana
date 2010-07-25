dojo.provide("dsms.widgets.Blocker");

dojo.declare("dsms.widgets.Blocker", null, {
// use :
//      blocker = new dsms.widgets.Blocker("node_id");
//      blocker.show()
//      blocker.hide()
// and in css something like:
//      .dojoBlockOverlay {
//          background:#fff url(http://ajax.googleapis.com/ajax/libs/dojo/1.3.0/dojox/image/resources/images/loading.gif) no-repeat center center;
//      }
            duration: 400,
            opacity: 0.6,
            backgroundColor: "#fff",
            zIndex: 999,

            constructor: function(node, args){
                    // mixin the passed properties into this instance
                    dojo.mixin(this, args);
                    this.node = dojo.byId(node);

                    // create a node for our overlay.
                    this.overlay = dojo.doc.createElement('div');

                    // do some chained magic nonsense
                    dojo.query(this.overlay)
                            .place(dojo.body(),"last")
                            .addClass("dojoBlockOverlay")
                            .style({
                                    backgroundColor: this.backgroundColor,
                                    position: "absolute",
                                    zIndex: this.zIndex,
                                    display: "none",
                                    opacity: this.opacity
                            });
            },

            show: function(){
                // summary: Show this overlay
                var pos = dojo.coords(this.node, true),
                        ov = this.overlay;

                dojo.marginBox(ov, pos);
                dojo.style(ov, { opacity:0, display:"block" });
                dojo.anim(ov, { opacity: this.opacity }, this.duration);
            },

            hide: function(){
                // summary: Hide this overlay
                dojo.fadeOut({
                        node: this.overlay,
                        duration: this.duration,
                        // when the fadeout is done, set the overlay to display:none
                        onEnd: dojo.hitch(this, function(){
                                dojo.style(this.overlay, "display", "none");
                        })
                }).play();
            }
    });