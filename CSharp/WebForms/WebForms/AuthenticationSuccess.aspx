<%@ Page Language="C#" MasterPageFile="~/Site.Master" AutoEventWireup="true" CodeBehind="AuthenticationSuccess.aspx.cs" Inherits="WebForms.AuthenticationSuccess" %>

<%@ PreviousPageType VirtualPath="~/Authentication.aspx" %>
<%@ Import Namespace="Lacuna.RestPki.Client" %>

<asp:Content ID="BodyContent" ContentPlaceHolderID="MainContent" runat="server">

	<h2>Authentication successful</h2>

	<p>User certificate information:</p>
	<ul>
		<li>Subject: <%= certificate.SubjectName.CommonName %></li>
		<li>Email: <%= certificate.EmailAddress %></li>
		<%-- Less common fields in the subject name and issuer name are also accessible: --%>
		<% if (!string.IsNullOrEmpty(certificate.SubjectName[Name.Oids.GivenName])) { %>
			<li>Given name: <%= certificate.SubjectName[Name.Oids.GivenName] %></li>
		<% } %>
		<% if (!string.IsNullOrEmpty(certificate.SubjectName[Name.Oids.Surname])) { %>
			<li>Surname: <%= certificate.SubjectName[Name.Oids.Surname] %></li>
		<% } %>
		<li>ICP-Brasil fields
			<ul>
				<li>Tipo de certificado: <%= certificate.PkiBrazil.CertificateType %></li>
				<li>CPF: <%= certificate.PkiBrazil.Cpf %></li>
				<li>Responsavel: <%= certificate.PkiBrazil.Responsavel %></li>
				<li>Empresa: <%= certificate.PkiBrazil.CompanyName %></li>
				<li>CNPJ: <%= certificate.PkiBrazil.Cnpj %></li>
			</ul>
		</li>
	</ul>
</asp:Content>
