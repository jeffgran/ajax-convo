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
   
   var ac = {
      
   };
   
   
   
   
   
});