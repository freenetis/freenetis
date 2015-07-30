<applet code='com.mindbright.application.MindTerm.class' archive='<?php echo url::base() . 'media/java/mindterm.jar'; ?>' width=100% height=450px>
    <param name='sepframe' value='false' />

    <param name='protocol' value='ssh2' />

    <param name='server' value='<?php echo $ip; ?>' />
    <param name='username' value='<?php echo $this->session->get('user_name'); ?>' />
    <param name='port' value='22' />
    <param name='alive' value='60' />
    <param name='bg-color' value='black' />
    <param name='fg-color' value='white' />
    <param name='cursor-color' value='white' />
    <param name='menus' value='yes' />
    <param name="te" value="xterm-color">
    <param name="gm" value="80x32">
    <param name="cipher" value="3des">
    <param name="quiet" value="false">
    <param name="cmdsh" value="true">
    <param name="verbose" value="true">
    <param name="autoprops" value="none">
    <param name="idhost" value="false">
</applet> 
<br />
<br />
<a href="http://www.appgate.com/index/products/mindterm/run_mindterm.html"><?php echo __('documentation') ?></a>