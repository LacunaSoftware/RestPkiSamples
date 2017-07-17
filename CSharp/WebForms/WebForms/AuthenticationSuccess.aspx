<%@ Page Language="C#" MasterPageFile="~/Site.Master" AutoEventWireup="true" CodeBehind="AuthenticationSuccess.aspx.cs" Inherits="WebForms.AuthenticationSuccess" %>

<%@ PreviousPageType VirtualPath="~/Authentication.aspx" %>

<asp:Content ID="BodyContent" ContentPlaceHolderID="MainContent" runat="server">

	<h2>Authentication successful</h2>

	<p>User certificate information:</p>
	<ul>
		<li>Subject: <%= certificate.SubjectName.CommonName %></li>
		<li>Email: <%= certificate.EmailAddress %></li>
		<li>ICP-Brasil fields
			<ul>
				<li>Tipo de certificado: <%= certificate.PkiBrazil.CertificateType %></li>
				<li>CPF: <%= certificate.PkiBrazil.Cpf %></li>
				<li>Responsavel: <%= certificate.PkiBrazil.Responsavel %></li>
				<li>Empresa: <%= certificate.PkiBrazil.CompanyName %></li>
				<li>CNPJ: <%= certificate.PkiBrazil.Cnpj %></li>
                <li>RG: <%= certificate.PkiBrazil.RGNumero %> <%= certificate.PkiBrazil.RGEmissor %> <%= certificate.PkiBrazil.RGEmissorUF %></li>
                <li>OAB: <%= certificate.PkiBrazil.OabNumero %> <%= certificate.PkiBrazil.OabUF %></li>
			</ul>
		</li>
	</ul>
</asp:Content>
