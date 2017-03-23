Supported tags on PAdES visual representation
=============================================

The following tags are supported:

* `{{name}}` - The best guess for the signer's full name (recommended for this purpose over `{{subject_cn}}`)
* `{{national_id}}` - The best guess for the signer's national ID. For ICP-Brasil certificates, this is resolved to the holder's CPF. For Italian certificates, this is resolved to the holder's *codice fiscale*.
* `{{email}}` - Signer's email address
* `{{subject_cn}}` - The Common Name (CN) part of the certificate's subject name field
* `{{issuer_cn}}` - The Common Name (CN) part of the certificate's issuer name field

ICP-Brasil specific tags
------------------------

* `{{br_cpf}}` - Certificate holder's CPF (*CPF do titular/responsável*)
* `{{br_cpf_formatted}}` - Same as `{{br_cpf}}` but formatted as **000.000.000-00**
* `{{br_cnpj}}` - Company's CNPJ
* `{{br_cnpj_formatted}}` - Same as `{{br_cnpj}}` but formatted as **00.000.000/0000-00**
* `{{br_responsavel}}` - Name of the certificate's holder (*nome do titular/responsável*)
* `{{br_company}}` - Company name
* `{{br_oab_numbero}}` - OAB's *Número de Inscrição junto a Seccional* (without leading zeroes)
* `{{br_oab_uf}}` - OAB's *sigla do Estado da Seccional*
* `{{br_rg_numero}}` - Certificate holder's ID  number (*número do RG do titular/responsável*) without leading zeroes
* `{{br_rg_emissor}}` - Issuing entity of the certificate holder's ID (órgão emissor do RG do titular/responsável)
* `{{br_rg_uf}}` - State code of the issuing entity of the certificate holder's ID (*UF do órgão emissor do RG do titular/responsável*)

Aliases
-------

The following tags are supported for backward compatibility:

* `{{signerName}}` - same as `{{name}}`
* `{{signerEmail}}` - same as `{{email}}`
* `{{signerNationalId}}` - same as `{{national_id}}`
* `{{issuerCommonName}}` - same as `{{issuer_cn}}`
