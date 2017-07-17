<%@ Page Language="C#" MasterPageFile="~/Site.Master" AutoEventWireup="true" CodeBehind="AuthenticationFail.aspx.cs" Inherits="WebForms.AuthenticationFail" %>

<%@ PreviousPageType VirtualPath="~/Authentication.aspx" %>

<asp:Content ID="BodyContent" ContentPlaceHolderID="MainContent" runat="server">

	<h2>Authentication Failed</h2>
	<p><%= vrHtml %></p>
	<p><a href="/Authentication" class="btn btn-primary">Try again</a></p>
	
</asp:Content>

