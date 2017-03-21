<%@ Page Language="C#" MasterPageFile="~/Site.Master" AutoEventWireup="true" CodeBehind="XmlElementSignature.aspx.cs" Inherits="WebApplication1.XmlElementSignature" %>

<%--
	Include the following javascript files:
	- jquery.blockUI.js       : jQuery plugin to block the UI
	- lacuna-web-pki-2.5.0.js : Javascript library to access the Web PKI component (client-side component used to access the user's certificates)
	- signature-form.js       : Javascript code to call the Web PKI component
--%>
<asp:Content ID="HeadContent" ContentPlaceHolderID="HeadContent" runat="server">
    <script type="text/javascript" src="Scripts/jquery.blockUI.js"></script>
    <script type="text/javascript" src="Scripts/lacuna-web-pki-2.5.0.js"></script>
    <script type="text/javascript" src="Scripts/signature-form.js"></script>
</asp:Content>

<asp:Content ID="BodyContent" ContentPlaceHolderID="MainContent" runat="server">
	
	<h2>XML Element Signature</h2>
	
	<%-- Render a select (combo box) to list the user's certificates. For now it will be empty, we'll populate it later on (see javascript below). --%>
	<select id="certificateSelect"></select>
	
	<%--
		Action buttons. Notice that both buttons have a OnClientClick attribute, which calls the
		client-side javascript functions "sign" and "refresh" below. Both functions return false,
		which prevents the postback.
	--%>
	<asp:Button ID="SignButton" runat="server" Text="Sign File" OnClientClick="return sign();" />
	<asp:Button ID="RefreshButton" runat="server" Text="Refresh" OnClientClick="return refresh();" />

	<%--
		Hidden button whose click event is fired by the "signature form" javascript upon completion
		of the signature process. Notice that we cannot use Visible="False" otherwise ASP.NET will 
		omit the button altogether from the rendered page, making it impossible to programatically
		"click" it.
	--%>
	<asp:Button ID="SubmitButton" runat="server" OnClick="SubmitButton_Click" style="display: none;"  />

	<script>
		<%--
			Once the page is loaded, we'll call the init() function on the signature-form.js file passing the token
			acquired from Rest PKI and references to the certificates select element and the button that should be
			triggered once the operation is completed.
		--%>
		$(function () {
			signatureForm.init({
				token: '<%= ViewState["Token"] %>',
				certificateSelect: $('#certificateSelect'),
				submitButton: $('#<%= SubmitButton.ClientID %>')
			});
		});
		<%-- Client-side function called when the user clicks the "Sign File" button --%>
		function sign() {
			signatureForm.sign();
			return false; // prevent postback
		}
		<%-- Client-side function called when the user clicks the "Refresh" button --%>
		function refresh() {
			signatureForm.refresh();
			return false; // prevent postback
		}
	</script>
</asp:Content>
