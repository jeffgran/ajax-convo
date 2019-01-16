$('document').ready(function() {
   
   //init
   var msgDefaultText = 'Type Something';
   var editTimer = 0;
   messageClose();
   
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
      return this.replace(/<br>/g, '\n');
   }
   
   //message Class
   function messageClass(arg) {
      var that = this;
      if (typeof(arg) == "string") {
         if (!(this.ID = $('.msgID').val()))
            this.ID = new Date().getTime();
         this.text = arg.nl2br();
      } else if (typeof arg == "object") {
         this.ID = $(arg).parent().parent().attr("id").replace(/message_/g, "");
         //$('#message_' + this.ID).html($("#message_" + this.ID).html().replace(/\n/g, '').replace(/<br>/g, '\n'));
         //$("#message_" + this.ID).children().remove();
         this.text = $("#message_" + this.ID + " p").html().trim();//.br2nl();
      }
      this.create = function() {
         $.post("ac_submit.php", { act : "create", msg : this.text, ID : this.ID }, this.create_response);
      }
      this.create_response = function(data, status) {
         if(status=="success") {
            that.display(function(data){
               $('#messageForm').hide().slideDown('slow');
               $('#messages').append(data);
               $('#message').val('').focus();
            });
         }
      }
      this.display = function(func) {
         $.post("ac_submit.php", { act: "display", ID : that.ID, msg : that.text }, func);
      }
      this.remove = function() {
         $.post("ac_submit.php", { act : "remove",  ID : this.ID }, this.remove_response);
      }
      this.remove_response = function(data, status) {
         $("#message_" + that.ID).slideUp('slow');
      }
      this.edit = function() {
         $("#messages").addClass("editMode");
         $('#share').val("Done");
         $('#message').val(this.text.br2nl()).focus();
         $("#message_" + this.ID).replaceWith($('#messageForm'));
         // save the ID and orig. Msg in hidden fields for when done editing
         $('#messageForm').append('<input class="msgID" type="hidden" name="id" value="' + this.ID + '"/>');
         $('#messageForm').append('<input class="msgMsg" type="hidden" name="msg" value="' + this.text + '"/>');
         messageOpen();
      }
      this.done = function() {
         $.post("ac_submit.php", { act: "edit", ID: this.ID, msg : this.text }, this.edit_response);
         $('.msgID').remove();
         $('.msgMsg').remove();
      }
      this.cancel = function() {
         that.display(function(data){
            messageClose();
            $('#messageForm').before(data);
            $('#messages').after($('#messageForm'));
            $('#share').val('Share');
            $("#messages").removeClass("editMode");
         });
         $('.msgID').remove();
         $('.msgMsg').remove();
      }
      this.edit_response = function(data, status) {
         $('#messageForm').before(data);
         $('#messages').after($('#messageForm'));
         $('#share').val('Share');
         messageClose();
         $("#messages").removeClass("editMode");
      }
   }
   
   function messageClose() {
      $('#message').val(msgDefaultText)
         .addClass('message_prompt');
      $('#share').hide();
      if($('#messages').hasClass("editMode"))
         $('#cancel').hide();
   }
   function messageOpen() {
      $('#message').focus().removeClass('message_prompt');
      $('#share').show();
      if($('#messages').hasClass("editMode"))
         $('#cancel').show();
         
   }
   
   // textarea focus/blur
   $('#message').focus(function(){
      if($(this).val() == msgDefaultText) {
         $(this).val('');
         messageOpen();
      }
   });
   $('#message').blur(function(){
      var msg = $('#message').val().stripHTML();
      if($("#messages").hasClass('editMode')) {
         
       } else if(msg == '') {
          editTimer = setTimeout(messageClose, 200);
       }
    });
    // enter key with and without shift
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
   $('#share').click(function(evt) {
      evt.preventDefault();
      var msg = $('#message').val().stripHTML();
      if(!$('#messages').hasClass("editMode")) {
         if(msg != '') {
            var msgObj = new messageClass(msg);
            msgObj.create();
         } else {
              self.setTimeout(messageClose, 200);
         }
      } else {
         var msgObj = new messageClass(msg);
         $('#message').val('').focus();
         msgObj.done();
      }
   });
   $('#cancel').click(function(e) {
      e.preventDefault();
      var msgObj = new messageClass($('.msgMsg').val());
      msgObj.cancel();
   });
   
   
   $('.deleteButton').live("click", function(e) {
      e.preventDefault();
      var msgObj = new messageClass(this);
      msgObj.remove();
   });
   
   $('.editButton').live("click", function(e) {
      e.preventDefault();
      clearTimeout(editTimer);
      var msgObj = new messageClass(this);
      msgObj.edit();
   });
   
});