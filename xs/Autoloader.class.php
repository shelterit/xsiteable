<?php

    namespace xs ;

/**
 * SplClassLoader implementation that implements the technical interoperability
 * standards for PHP 5.3 namespaces and class names.
 *
 * http://groups.google.com/group/php-standards/web/final-proposal
 *
 *     // Example which loads classes for the Doctrine Common package in the
 *     // Doctrine\Common namespace.
 *     $classLoader = new SplClassLoader('Doctrine\Common', '/path/to/doctrine');
 *     $classLoader->register();
 *
 * @author Jonathan H. Wage <jonwage@gmail.com>
 * @author Roman S. Borschel <roman@code-factory.org>
 * @author Matthew Weier O'Phinney <matthew@zend.com>
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 * @author Fabien Potencier <fabien.potencier@symfony-project.org>
 */
class SplClassLoader
{
    private $_fileExtension = '.class.php';
    private $_fileExtensionNormal = '.php';
    private $_namespace;
    private $_includePath;
    private $_namespaceSeparator = '\\';

    /**
     * Creates a new <tt>SplClassLoader</tt> that loads classes of the
     * specified namespace.
     * 
     * @param string $ns The namespace to use.
     */
    public function __construct($ns = null, $includePath = null)
    {
        $this->_namespace = $ns;
        $this->_includePath = $includePath;
    }

    /**
     * Sets the namespace separator used by classes in the namespace of this class loader.
     * 
     * @param string $sep The separator to use.
     */
    public function setNamespaceSeparator($sep)
    {
        $this->_namespaceSeparator = $sep;
    }

    /**
     * Gets the namespace seperator used by classes in the namespace of this class loader.
     *
     * @return void
     */
    public function getNamespaceSeparator()
    {
        return $this->_namespaceSeparator;
    }

    /**
     * Sets the base include path for all class files in the namespace of this class loader.
     * 
     * @param string $includePath
     */
    public function setIncludePath($includePath)
    {
        $this->_includePath = $includePath;
    }

    /**
     * Gets the base include path for all class files in the namespace of this class loader.
     *
     * @return string $includePath
     */
    public function getIncludePath()
    {
        return $this->_includePath;
    }

    /**
     * Sets the file extension of class files in the namespace of this class loader.
     * 
     * @param string $fileExtension
     */
    public function setFileExtension($fileExtension)
    {
        $this->_fileExtension = $fileExtension;
    }

    /**
     * Gets the file extension of class files in the namespace of this class loader.
     *
     * @return string $fileExtension
     */
    public function getFileExtension()
    {
        return $this->_fileExtension;
    }

    /**
     * Installs this class loader on the SPL autoload stack.
     */
    public function register()
    {
        spl_autoload_register(array($this, 'loadClass'));
    }

    /**
     * Uninstalls this class loader from the SPL autoloader stack.
     */
    public function unregister()
    {
        spl_autoload_unregister(array($this, 'loadClass'));
    }

    /**
     * Loads the given class or interface.
     *
     * @param string $className The name of the class to load.
     * @return void
     */
    public function loadClass($className)
    {
        global $xs_stack ;
        
        $namespace = substr($className, 0, stripos($className, $this->_namespaceSeparator));
        $path = str_replace ( '_', DIRECTORY_SEPARATOR, $className ) ;
        $path = str_replace ( $this->_namespaceSeparator, DIRECTORY_SEPARATOR, $path ) ;

        
        if ( $namespace == 'xs' ) {
            
            $fileName = \xs\Core::$dir . DIRECTORY_SEPARATOR . $path . $this->_fileExtension ;
            
            // echo "<hr> [ $namespace | $className | $path | '$fileName' ] " ;
            
            if ( file_exists ( $fileName ) ) {
                
                // echo "[$inc] " ;
                
                require_once ( $fileName ) ;
                if ( is_object ( $xs_stack) ) 
                    $xs_stack->add_dynamic_file ( $fileName ) ;
                
                if ( 
                        class_exists ( '\xs\Core' ) &&
                        isset ( \xs\Core::$glob ) &&
                        is_object ( \xs\Core::$glob->log ) 
                )
                    \xs\Core::$glob->log->add ( "__auto: [$fileName]" ) ;
            } else {
                
                echo "<div>xSiteable Autoloader : $fileName <b style='color:red'>not</b> found. [$className]</div>" ;
            }
            
         } else {
             
            $fileName = \xs\Core::$dir_lib . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . $path . $this->_fileExtensionNormal ;

            // echo "<br/>[ '$fileName' ] " ;
            if ( file_exists ( $fileName ) ) {
                // echo "Yay! There! " ;
                require_once ( $fileName ) ;
            } else {
                echo "<div>xSiteable Autoloader : $fileName <b style='color:red'>not</b> found. [$className]</div>" ;
            }
         }
    }
}

    class Autoloader {
        
        public function autoload ( $className ) {
            
            global $xs_stack ;

            // ignore all classes starting with an underline
            if ( substr ( $className, 0, 1 ) == '_' )
               return ;

            /*
            $className = ltrim($class_name, '\\');
            $fileName  = '';
            $namespace = '';
            if ($lastNsPos = strrpos($className, '\\')) {
                $namespace = substr($className, 0, $lastNsPos);
                $className = substr($className, $lastNsPos + 1);
                $fileName  = str_replace('\\', DIRECTORY_SEPARATOR, $className) . DIRECTORY_SEPARATOR;
            }
            $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.class.php';
            
            // we're including the main class only
            $inc = \xs\Core::$dir_xs . DIRECTORY_SEPARATOR . $fileName ;
            */
            
            $className = ltrim($className, '\\');
            $fileName  = '';
            $namespace = '';
            if ($lastNsPos = strrpos($className, '\\')) {
                $namespace = substr($className, 0, $lastNsPos);
                $className = substr($className, $lastNsPos + 1);
                $fileName  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
            }
            $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.class.php';
            
            $inc = \xs\Core::$dir_xs . DIRECTORY_SEPARATOR . $fileName ;
            echo "<hr> [ $namespace | $className | $fileName | '$inc' ] " ;
            
            /*
            // does the class start with 'xs_'?
            if ( substr ( $class_name, 0, 3 ) == 'xs_' ) {
                // yes?
                if ( $len = strpos ( $class_name, '_', 3 ) ) {

                    // does the class contain a sub-class, but located in the same directory as a main?
                    $short = substr ( $class_name, 0, $len ) ;
                    $inc = \xs\Core::$dir_xs . "/$short/$class_name.class.php" ;

                } else

                    // we're including the main class only
                    $inc = \xs\Core::$dir_xs . "/$class_name/$class_name.class.php" ;

            } else {
                // No? Look in /classes/$class_name.php
                $inc = \xs\Core::$dir_app . "/classes/$class_name.php" ;
            }
            */
            
            if (file_exists($inc)) {
                
                // echo "[$inc] " ;
                
                require_once ( $inc ) ;
                if ( is_object ( $xs_stack) ) 
                    $xs_stack->add_dynamic_file ( $inc ) ;
                
                if ( 
                        class_exists ( '\xs\Core' ) &&
                        isset ( \xs\Core::$glob ) &&
                        is_object ( \xs\Core::$glob->log ) 
                )
                    \xs\Core::$glob->log->add ( "__auto: [$inc]" ) ;
            } else {
                
                echo "<b style='color:red'>not</b> found. " ;
                // used to be verbose, but now fails quietly in case there are
                // other autoloaders
                // 
                // xs_Core::$glob->log->add ( "__auto FAIL: [$inc]" ) ;
                // echo "xs_autoloader failed to find file [$inc] ($class_name) short=[$short]<br/> " ;
            }

        }
        
    }

