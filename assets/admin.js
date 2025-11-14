
jQuery(function($){
    $('#cpl-test-key').on('click', function(){
        let key = $('#api_key').val();
        $('#cpl-test-result').text('Testando...');
        $.post(CPLMC.ajax, {
            action:'cpl_mc_test_key',
            api_key:key,
            _ajax_nonce:CPLMC.nonce
        }, function(res){
            if(res.success){
                $('#cpl-test-result').css('color','green').text(res.data);
            } else {
                $('#cpl-test-result').css('color','red').text(res.data);
            }
        });
    });

    $('#cpl-fetch-audiences').on('click', function(){
        let key = $('#api_key').val();
        let $select = $('#cpl-audience-select');
        let current = $select.data('current') || '';

        $select.html('<option>Carregando...</option>');

        $.post(CPLMC.ajax, {
            action:'cpl_mc_fetch_audiences',
            api_key:key,
            _ajax_nonce:CPLMC.nonce
        }, function(res){
            if(res.success){
                let html = '<option value="">-- Selecione --</option>';
                res.data.forEach(list=>{
                    let selected = (list.id === current) ? ' selected' : '';
                    html += `<option value="${list.id}"${selected}>${list.name}</option>`;
                });

                $select.html(html);
            } else {
                alert(res.data || 'Erro ao carregar Audiences.');
            }
        });
    });
});
