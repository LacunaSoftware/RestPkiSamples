Rails.application.routes.draw do

    root :to => 'home#index'

    get 'upload', to: 'upload#index'
    post 'upload', to: 'upload#action'

    get 'authentication', to: 'authentication#index'
    post 'authentication', to: 'authentication#action'

    get 'pades_signature', to: 'pades_signature#index'
    post 'pades_signature', to: 'pades_signature#action'

    get 'pades_signature_without_integration', to: 'pades_signature_without_integration#index'
    post 'pades_signature_without_integration', to: 'pades_signature_without_integration#action'

    get 'cades_signature', to: 'cades_signature#index'
    post 'cades_signature', to: 'cades_signature#action'

    get 'full_xml_signature', to: 'full_xml_signature#index'
    post 'full_xml_signature', to: 'full_xml_signature#action'

    get 'xml_element_signature', to: 'xml_element_signature#index'
    post 'xml_element_signature', to: 'xml_element_signature#action'

    get 'batch_signature', to: 'batch_signature#index'
    post 'batch_signature/start/:id', to: 'batch_signature#start'
    post 'batch_signature/complete/:id', to: 'batch_signature#complete'

    get 'cades_batch_signature', to: 'cades_batch_signature#index'
    post 'cades_batch_signature/start/:id', to: 'cades_batch_signature#start'
    post 'cades_batch_signature/complete/:id', to: 'cades_batch_signature#complete'

    get 'xml_element_batch_signature', to: 'xml_element_batch_signature#index'
    post 'xml_element_batch_signature/start', to: 'xml_element_batch_signature#start'
    post 'xml_element_batch_signature/complete', to: 'xml_element_batch_signature#complete'

end
