<%@ Page Language="C#" MasterPageFile="~/Site.Master" AutoEventWireup="true" CodeBehind="Default.aspx.cs" Inherits="WebApplication1._Default" %>
<asp:Content ID="BodyContent" ContentPlaceHolderID="MainContent" runat="server">

	<h2>REST PKI Samples</h2>
	Choose one of the following samples:
	<ul>
		<li><a href="/Authentication.aspx">Authentication with digital certificate</a></li>
		<li>
			Create a PAdES signature
			<ul>
				<li><a href="/PadesSignature.aspx">With a file already on server</a></li>
				<%--<li><a href="/Upload?rc=PadesSignature">With a file uploaded by user</a></li>--%>
			</ul>
		</li>
		<li>
			Create a CAdES signature
			<ul>
				<li><a href="/CadesSignature.aspx">With a file already on server</a></li>
				<%--<li><a href="/Upload?rc=CadesSignature">With a file uploaded by user</a></li>--%>
			</ul>
		</li>
		<li>
			Create a XML signature
			<ul>
				<%--<li><a href="/XmlFullSignature">Full XML signature (enveloped signature)</a></li>--%>
				<li><a href="/XmlElementSignature.aspx">XML element signature</a></li>
			</ul>
		</li>
		<li><a href="/BatchSignature.aspx">Sign a batch of files</a></li>
	</ul>

</asp:Content>


