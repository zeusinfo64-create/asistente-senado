
const Footer = () => {
  const d = new Date();
  return (
    <div className="footer">
      <div className="copyright">
        <p>
          Mesa de Ayuda TI &copy; {d.getFullYear()} - Unidad de Tecnologías de
          Información
        </p>
      </div>
    </div>
  );
};

export default Footer;