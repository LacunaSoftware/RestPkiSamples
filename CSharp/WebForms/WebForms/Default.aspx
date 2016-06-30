<%@ Page Title="Home Page" Language="C#" MasterPageFile="~/Site.Master" AutoEventWireup="true" CodeBehind="Default.aspx.cs" Inherits="WebForms._Default" %>

<asp:Content ID="BodyContent" ContentPlaceHolderID="MainContent" runat="server">

	<h2>REST PKI Samples</h2>
	Choose one of the following samples:
	<ul>
		<%--<li><a href="/Authentication">Authentication with digital certificate</a></li>--%>
		<%--<li>
			Create a PAdES signature
			<ul>
				<li><a href="/PadesSignature">With a file already on server</a></li>
				<li><a href="/Upload?rc=PadesSignature">With a file uploaded by user</a></li>
			</ul>
		</li>--%>
		<li>
			Create a CAdES signature
			<ul>
				<li><a href="/CadesSignature">With a file already on server</a></li>
				<%--<li><a href="/Upload?rc=CadesSignature">With a file uploaded by user</a></li>--%>
			</ul>
		</li>
		<%--<li>
			Create a XML signature
			<ul>
				<li><a href="/XmlFullSignature">Full XML signature (enveloped signature)</a></li>
				<li><a href="/XmlElementSignature">XML element signature</a></li>
			</ul>
		</li>--%>
		<%--<li>
			Sign a batch of files
			<ul>
				<li><a href="/BatchSignature">Simple batch signature</a></li>
				<li><a href="/BatchSignatureOptimized">Optimized batch signature</a> (better performance but more complex Javascript)</li>
			</ul>
		</li>--%>
	</ul>

</asp:Content>


