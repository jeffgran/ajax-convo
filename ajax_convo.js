$(document).ready(function() {
   
   String.prototype.trim = function() {
      return this.replace(/^\s*/, "").replace(/\s*$/, "");
   }
   String.prototype.stripHTML = function() {
      return this.replace(/<\S[^><]*>/g, "");
   }
   String.prototype.nl2br = function() {
      return this.replace(/\n/g, '<br />');
   }
   String.prototype.br2nl = function() {
      return this.replace(/<br>/ig, '\n');
   }
   String.prototype.getNumFromID = function() {
      return this.replace(/[a-zA-Z_]/g, "");
   }
   
   
   //==============
   // State Object
   //==============
   var AC_State = (function() {
      var mode = "standardMode",
          modes = "standardMode editMode createThreadMode",
          currentThreadID = 0;
          
      this.getMode = function() {
         return mode;
      }
      this.inMode = function(q) {
         return (q == mode);
      }
      this.setMode = function(newmode) {
         $('body').removeClass(modes).addClass(newmode);
         mode = newmode;
      }
      
      this.setCurrentThread = function(threadID) {
         currentThreadID = threadID;
      }
      this.getCurrentThread = function() {
         return currentThreadID;
      }
      return this;
   })();
   
   
   //==============
   // Message Class
   //==============
   function MessageClass(arg) {
      var that = this;
      
      // string should be the msg text.
      if (typeof arg == "string") {
         // get the stored id from the hidden field if available, default to a new ID
         this.ID = $('.msgID').val() || null;
         this.text = arg.nl2br();
      // object should be the button that was clicked.
      } else if (typeof arg == "object") {
         // get the ID from the button's ID
         this.ID = $(arg).attr("id").getNumFromID();
         // get the msg text from the p tag for this ID
         this.text = $("#message_" + this.ID + " p").html().trim();
      }
      
      this.create = function() {
         $.ajax({
            data: {
               act : "createMsg",
               msg : this.text,
               threadID : $('.threadLink_current').attr("id").getNumFromID()
            },
            success: function(newID) {
               that.ID = newID;
               that.display(function(data){
                  $('#messageForm').hide().slideDown('slow', function() {$('#message').focus();});
                  $('#messages').append(data);
                  $('#message').val('')
               });
            }
         });
      }
      
      this.remove = function() {
         $.ajax({
            data:{
               act : "removeMsg",
               ID : this.ID,
               threadID : $('.threadLink_current').attr("id").getNumFromID()
            }, 
            success: function(data) {
               $("#message_" + that.ID).slideUp('slow', function() {
                  $(this).remove()
                  // if we just removed the last one...
                  if ($("#messages").children().size() == 0 ) {
                     //alert("empty now");
                     $("#thread_" + AC_State.getCurrentThread()).remove();
                     $('#threads').children().slice(0, 1).click();
                  }
               });
            }
         });
      }
      
      this.edit = function() {
         AC_State.setMode("editMode");
         $('#share').val("Done");
         $('#message').val(this.text.br2nl()).focus();
         $("#message_" + this.ID).replaceWith($('#messageForm'));
         // save the ID and orig. Msg in hidden fields for when done editing
         $('#messageForm').append('<input class="msgID" type="hidden" name="id" value="' + this.ID + '"/>');
         $('#messageForm').append('<input class="msgMsg" type="hidden" name="msg" value="' + this.text + '"/>');
         formOpen();
      }
      
      this.doneEditing = function() {
         $.ajax({
            data: {
               act: "editMsg",
               ID: this.ID,
               msg : this.text,
               threadID : $('.threadLink_current').attr("id").getNumFromID()
            },
            success: that.display(function(data) {
               $('#messageForm').before(data);
               $('#messages').after($('#messageForm'));
               $('#share').val('Share');
               formClose();
               AC_State.setMode("standardMode");
               $('.msgID').remove();
               $('.msgMsg').remove();
            })
         });
      }
      
      this.cancelEditing = function() {
         that.display(function(data){
            formClose();
            $('#messageForm').before(data);
            $('#messages').after($('#messageForm'));
            $('#share').val('Share');
            AC_State.setMode("standardMode");
         });
         $('.msgID').remove();
         $('.msgMsg').remove();
      }
      
      this.display = function(func) {
         var toDisplay = msgTemplate.replace(/{id}/g, that.ID).replace(/{msg}/g, that.text);
         func(toDisplay);
      }
   }
   
   
   //===================
   // Functions
   //===================
   function formClose() {
      $('#message').val(msgDefaultText)
         .addClass('message_prompt');
      $('#share').hide();
      if (AC_State.inMode("editMode")) {
         $('#cancel').hide();
      }
   }
   
   function formOpen() {
      $('#message').focus().removeClass('message_prompt');
      $('#share').show();
      if (AC_State.inMode("editMode")) {
         $('#cancel').show();
      }
   }
   
   
   //=============
   // init/globals
   //=============
   var msgDefaultText = 'Type Something';
   var editTimer = 0;
   
   $.ajaxSetup({ 
      url: "ac_submit.php",
      type: "POST",
      beforeSend: function() {
         $("#message, #share").attr("disabled", "true");
      },
      complete: function() {
         $("#message, #share").removeAttr("disabled");
      }
   });
   
   // get the template from the server and store it here.
   var msgTemplate;
   $.ajax({
      data: {
         act : "getMsgTemplate",
         template : "default"
         },
      success: function(data) { msgTemplate = data;}
   });
   
   formClose();
   
   
   //===================
   // Message Events
   //===================
   
   
   // textarea focus/blur
   $('#message').focus(function(){
      if($(this).val() == msgDefaultText) {
         $(this).val('');
         formOpen();
      }
   });
   $('#message').blur(function(){
      var msg = $('#message').val().stripHTML();
      if (AC_State.inMode("standardMode")) {
         if(msg == '') {
            editTimer = setTimeout(formClose, 200);
          }
       }
    });
    // enter key without shift = submit
    // with shift = (default) newline
    $('#message').keypress(function(e) {
       var shiftKey = (e.shiftKey) ? true : false;
       if (e.keyCode == '13') {
          if (!shiftKey) {
             e.preventDefault();
             $('#share').click();
          }
       }
    });
    
   // Sumbit button
   $('#share').click(function(e) {
      
      e.preventDefault();
      var msg = $('#message').val().stripHTML();
      if (!AC_State.inMode("editMode")) {
         if(msg != '') {
            var msgObj = new MessageClass(msg);
            msgObj.create();
            AC_State.setMode("standardMode");
            $('#cancel').hide();
         } else {
            self.setTimeout(formClose, 200);
         }
      } else {
         var msgObj = new MessageClass(msg);
         $('#message').val('').focus();
         msgObj.doneEditing();
      }
   });
   
   // Cancel button
   $('#cancel').click(function(e) {
      if (AC_State.inMode("editMode")) {
         e.preventDefault();
         var msgObj = new MessageClass($('.msgMsg').val());
         msgObj.cancelEditing();
      } else if (AC_State.inMode("createThreadMode")) {
         e.preventDefault();
         AC_State.setMode("standardMode");
         // hide cancel button
         $('#cancel').hide();
         // close form
         formClose();
         // remove would-be new thread from thread list
         var threads = $('#threads').children();
         threads.slice(-1).remove();
         // re-display previously displayed thread
         //threads.slice(-2).click();
         var prevThreadID = AC_State.getCurrentThread();
         $('#thread_' + prevThreadID).click();
      }
   });
   
   
   $('.deleteButton').live("click", function(e) {
      e.preventDefault();
      var msgObj = new MessageClass(this);
      msgObj.remove();
   });
   
   $('.editButton').live("click", function(e) {
      e.preventDefault();
      clearTimeout(editTimer);
      var msgObj = new MessageClass(this);
      msgObj.edit();
   });
   
   
   //===================
   // Thread Events
   //===================
   $('.threadLink').live("click", function(e) {
      if (!AC_State.inMode("standardMode")) {
         return false;
      }
      var that = this;
      var threadID = $(this).attr("id").getNumFromID();
      e.preventDefault();
      $.ajax({
         data: {
            act : "showMessages",
            "threadID" : threadID
         },
         dataType: "json",
         success: function(data) {
            // remove existing messages
            $('.message').remove();
            // iterate over the JSON from the server
            for (var p in data) {
               var msgObj = new MessageClass(data[p].msg.trim());
               msgObj.ID = data[p].ID;
               // display each new message by 
               // appending it to the list
               msgObj.display(function(toDisplay) {
                  $('#messages').append(toDisplay);
               });
            }
            // clear the old "current" link and add it to the new one.
            $('.threadLink').removeClass("threadLink_current");
            $(that).addClass("threadLink_current");
            AC_State.setCurrentThread(threadID);
         }
      });
   });
   
   $('#createThreadLink').click(function(e) {
      if (!AC_State.inMode("standardMode")) {
         return false;
      }
      e.preventDefault();
      $.ajax({
         data: {
            act : "createThread",
         },
         success: function(data) {
            clearTimeout(editTimer);
            $('.threadLink').removeClass("threadLink_current");
            $('.message').remove();
            $('#threads').append('<a href="#" class="threadLink threadLink_current" id="thread_' + data + '">Thread ' + data + ": </a>");
            formOpen();
            $('#message').focus();
            $('#cancel').show();
            AC_State.setMode("createThreadMode");
            AC_State.setCurrentThread(data);
         }
      });
   });
   
   
   
   
   
});
